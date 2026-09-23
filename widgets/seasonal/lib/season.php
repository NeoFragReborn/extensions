<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Seasonal\Lib;

/**
 * La saison de l'effet décoratif : ses bornes, ses valeurs permises, et la question « est-ce le
 * moment ? ».
 *
 * Classe PURE, sans aucune dépendance au service locator — c'est ce qui la rend éprouvable par un
 * test unitaire, comme les providers de `widgets/twitch/lib`. Les contrôleurs ne font que l'appeler.
 */
class Season
{
	/** Les effets disponibles. Toute autre valeur retombe sur `snow`. */
	public const EFFETS = ['none', 'snow', 'confetti', 'leaves'];

	/** Densités proposées, et le nombre de particules correspondant. */
	public const DENSITES = ['low' => 40, 'normal' => 80, 'high' => 150];

	/**
	 * Sommes-nous dans la plage saisonnière ?
	 *
	 * Les bornes sont des jours de l'année (`MM-JJ`), sans millésime, et **les deux sont incluses**.
	 * Une plage vide vaut « toute l'année ».
	 *
	 * Le cas qui compte : une plage peut enjamber le Nouvel An — du 15 décembre au 6 janvier. La
	 * comparaison naïve `debut <= aujourd'hui <= fin` rendrait alors toujours faux, et l'effet ne
	 * marcherait jamais précisément quand on le veut le plus.
	 */
	public static function en_cours(string $debut, string $fin, ?string $aujourdhui = NULL): bool
	{
		$debut = self::jour_valide($debut);
		$fin   = self::jour_valide($fin);

		if ($debut === '' || $fin === '')
		{
			return TRUE; // plage non renseignée : l'effet vaut toute l'année
		}

		$jour = $aujourdhui ?? date('m-d');

		return $debut <= $fin
			? ($jour >= $debut && $jour <= $fin)      // dans la même année civile
			: ($jour >= $debut || $jour <= $fin);     // à cheval sur le Nouvel An
	}

	/**
	 * Un jour de l'année sous la forme `MM-JJ`, ou une chaîne vide.
	 *
	 * L'année n'y figure pas : une plage saisonnière se répète chaque année, et demander à
	 * l'administrateur de la ressaisir tous les ans serait une corvée qu'il oublierait.
	 *
	 * Une borne mal formée vaut « pas de borne », jamais « jamais » : un réglage importé d'un autre
	 * site ou saisi à la main ne doit pas faire disparaître l'effet sans rien dire.
	 *
	 * @param mixed $valeur
	 */
	public static function jour_valide($valeur): string
	{
		// Un réglage sérialisé peut rendre autre chose qu'une chaîne ; on refuse plutôt que de caster.
		if (!is_string($valeur) && !is_int($valeur))
		{
			return '';
		}

		$valeur = trim((string) $valeur);

		if (!preg_match('/^(\d{2})-(\d{2})$/', $valeur, $m))
		{
			return '';
		}

		$mois = (int) $m[1];
		$jour = (int) $m[2];

		// 29 février accepté : la plage se compare jour à jour, pas à une date réelle.
		if ($mois < 1 || $mois > 12 || $jour < 1 || $jour > 31)
		{
			return '';
		}

		return $valeur;
	}

	/**
	 * Normalise les réglages venus de la base.
	 *
	 * Tout ce qui sort d'ici part dans un attribut HTML puis dans du JavaScript : rien n'est repris
	 * tel quel. Une valeur inconnue retombe sur le défaut plutôt que de faire échouer la page.
	 *
	 * @param  array<string, mixed> $settings
	 * @return array{effect: string, density: string, from: string, to: string}
	 */
	public static function normaliser(array $settings): array
	{
		$effet   = $settings['effect']  ?? NULL;
		$densite = $settings['density'] ?? NULL;

		return [
			'effect'  => in_array($effet, self::EFFETS, TRUE)                        ? $effet   : 'snow',
			// `is_string` avant l'index : PHP 8.5 déprécie `NULL` comme clé de tableau, et un réglage
			// absent vaut justement NULL.
			'density' => is_string($densite) && isset(self::DENSITES[$densite])      ? $densite : 'normal',
			'from'    => self::jour_valide($settings['from'] ?? ''),
			'to'      => self::jour_valide($settings['to']   ?? ''),
		];
	}

	/** Le nombre de particules correspondant à une densité déjà normalisée. */
	public static function particules(string $densite): int
	{
		return self::DENSITES[$densite] ?? self::DENSITES['normal'];
	}
}
