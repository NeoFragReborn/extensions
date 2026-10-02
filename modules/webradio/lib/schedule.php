<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Webradio\Lib;

/**
 * La grille d'une webradio : ses créneaux, et ce qui passe à l'antenne maintenant.
 *
 * Classe PURE, sans dépendance au service locator. Deux choses y méritent une épreuve :
 *
 *   - un créneau peut **enjamber minuit** — « 22:00 → 02:00 » est une émission de nuit parfaitement
 *     ordinaire. La comparaison naïve `début <= maintenant <= fin` la rend toujours absente, et
 *     l'émission ne serait jamais à l'antenne précisément aux heures où elle passe. C'est le même
 *     motif que la saison qui enjambe le Nouvel An, rencontré sur l'effet saisonnier ;
 *   - l'**origine** du flux, qui part dans la politique de sécurité du site. Une origine mal extraite,
 *     et soit le flux est bloqué, soit on ouvre la politique plus large qu'il ne faut.
 */
class Schedule
{
	/** Les jours, dans l'ordre où une grille se lit. 1 = lundi, comme `date('N')`. */
	public const JOURS = [1, 2, 3, 4, 5, 6, 7];

	/**
	 * Une heure `HH:MM`, ou une chaîne vide.
	 *
	 * @param mixed $valeur
	 */
	public static function heure($valeur): string
	{
		if (!is_string($valeur))
		{
			return '';
		}

		$valeur = trim($valeur);

		if (!preg_match('/^(\d{1,2}):(\d{2})$/', $valeur, $m))
		{
			return '';
		}

		$h = (int) $m[1];
		$i = (int) $m[2];

		if ($h > 23 || $i > 59)
		{
			return '';
		}

		// Normalisée sur deux chiffres : « 9:05 » et « 09:05 » désignent la même heure, et deux
		// écritures différentes se compareraient mal.
		return sprintf('%02d:%02d', $h, $i);
	}

	/** @param mixed $valeur */
	public static function jour($valeur): int
	{
		$jour = is_numeric($valeur) ? (int) $valeur : 0;

		return in_array($jour, self::JOURS, TRUE) ? $jour : 1;
	}

	/**
	 * Le créneau est-il à l'antenne ?
	 *
	 * `$jour` suit `date('N')` : 1 pour lundi, 7 pour dimanche. Un créneau qui enjambe minuit
	 * appartient au jour où il COMMENCE — « samedi 22:00 → 02:00 » est l'émission du samedi soir,
	 * même quand on l'écoute à une heure du matin le dimanche.
	 */
	public static function en_direct(int $jour_creneau, string $debut, string $fin, int $jour_actuel, string $heure_actuelle): bool
	{
		$debut = self::heure($debut);
		$fin   = self::heure($fin);

		if ($debut === '' || $fin === '')
		{
			return FALSE;   // un créneau sans horaire lisible ne passe jamais
		}

		if ($debut < $fin)
		{
			return $jour_creneau === $jour_actuel && $heure_actuelle >= $debut && $heure_actuelle < $fin;
		}

		if ($debut === $fin)
		{
			// Début et fin identiques : vingt-quatre heures d'antenne, ce jour-là.
			return $jour_creneau === $jour_actuel;
		}

		// Le créneau enjambe minuit : il court de `début` à la fin de SON jour, puis du début du
		// jour SUIVANT jusqu'à `fin`.
		if ($jour_creneau === $jour_actuel && $heure_actuelle >= $debut)
		{
			return TRUE;
		}

		return self::lendemain($jour_creneau) === $jour_actuel && $heure_actuelle < $fin;
	}

	/** Le jour suivant, dimanche ramenant à lundi. */
	public static function lendemain(int $jour): int
	{
		return $jour === 7 ? 1 : $jour + 1;
	}

	/**
	 * Le créneau à l'antenne parmi ceux donnés, ou NULL.
	 *
	 * Le premier trouvé gagne : deux créneaux qui se chevauchent sont une erreur de grille, pas un
	 * cas à arbitrer ici. L'administration les affiche dans l'ordre de la grille, donc c'est le plus
	 * ancien qui l'emporte — comportement stable, et explicable.
	 *
	 * @param  list<array<string, mixed>> $creneaux
	 * @return array<string, mixed>|null
	 */
	public static function a_l_antenne(array $creneaux, ?int $jour = NULL, ?string $heure = NULL): ?array
	{
		// La grille est écrite à l'heure du site (Paramètres → Préférences générales) : « maintenant »
		// se lit donc dans ce fuseau, pas dans celui du serveur — à l'heure universelle, l'émission
		// annoncée en direct avait deux heures de retard pour un site français.
		if ($jour === NULL || $heure === NULL)
		{
			$maintenant = new \DateTime('now', nf_fuseau_site());
			$jour       = $jour ?? (int) $maintenant->format('N');
			$heure      = $heure ?? $maintenant->format('H:i');
		}

		foreach ($creneaux as $creneau)
		{
			if (self::en_direct(
				self::jour($creneau['day'] ?? 0),
				(string) ($creneau['start_time'] ?? ''),
				(string) ($creneau['end_time'] ?? ''),
				$jour,
				$heure
			))
			{
				return $creneau;
			}
		}

		return NULL;
	}

	/**
	 * L'origine d'une adresse — schéma, hôte et port — ou une chaîne vide.
	 *
	 * C'est ce qui part dans la directive `media-src` de la politique de sécurité. On veut l'origine
	 * et RIEN d'autre : y laisser le chemin n'aurait aucun effet (une directive de source ne compare
	 * que l'origine) et donnerait l'illusion d'une restriction plus fine qu'elle ne l'est.
	 *
	 * @param mixed $url
	 */
	public static function origine($url): string
	{
		if (!is_string($url))
		{
			return '';
		}

		$url = trim($url);

		if ($url === '' || !preg_match('#^https?://#i', $url))
		{
			return '';
		}

		$parties = parse_url($url);

		if (!$parties || empty($parties['host']))
		{
			return '';
		}

		$schema = strtolower((string) ($parties['scheme'] ?? 'https'));
		$hote   = strtolower((string) $parties['host']);

		// Un hôte ne contient que ces caractères. Tout le reste serait une injection dans l'en-tête
		// de politique, où l'espace sépare les sources.
		if (!preg_match('/^[a-z0-9.-]+$/', $hote))
		{
			return '';
		}

		$origine = $schema.'://'.$hote;

		if (!empty($parties['port']) && (int) $parties['port'] > 0 && (int) $parties['port'] <= 65535)
		{
			// Le port compte : une webradio écoute souvent sur 8000 plutôt que sur 443, et une
			// origine sans son port n'autoriserait pas le flux.
			$origine .= ':'.(int) $parties['port'];
		}

		return $origine;
	}

	/**
	 * L'adresse d'un flux qu'on accepte de servir au navigateur.
	 *
	 * Seuls `http` et `https` passent. `http` est accepté à regret : beaucoup de webradios n'ont pas
	 * de certificat, et le refuser rendrait le module inutile pour une bonne moitié d'entre elles.
	 * En revanche un flux `http` sur un site `https` sera refusé par le navigateur lui-même
	 * (contenu mixte) — c'est à l'administrateur que l'écran doit le dire, pas au silence.
	 *
	 * @param mixed $url
	 */
	public static function flux($url): string
	{
		if (!is_string($url))
		{
			return '';
		}

		$url = trim($url);

		return $url !== '' && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== FALSE ? $url : '';
	}

	/** Vrai si le flux sera refusé comme contenu mixte sur un site en HTTPS. */
	public static function contenu_mixte(string $flux): bool
	{
		return stripos($flux, 'http://') === 0;
	}
}
