<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Places\Lib;

/**
 * Les décisions du module Carte des lieux qui méritent d'être éprouvées.
 *
 * Classe PURE, sans dépendance au service locator. Tout tient aux coordonnées : elles partent dans
 * des attributs HTML, puis dans du JavaScript qui centre une carte. Une latitude hors bornes ne
 * lève aucune erreur — elle déplace simplement la carte quelque part d'autre, et personne ne
 * comprend pourquoi. C'est exactement le genre de défaut qu'un test attrape et qu'un œil rate.
 */
class Place
{
	/** Les bornes du monde. Au-delà, ce n'est pas un lieu, c'est une faute de frappe. */
	public const LAT_MIN = -90.0;
	public const LAT_MAX = 90.0;
	public const LON_MIN = -180.0;
	public const LON_MAX = 180.0;

	/** Niveaux de zoom acceptés par les tuiles d'OpenStreetMap. */
	public const ZOOM_MIN = 1;
	public const ZOOM_MAX = 19;
	public const ZOOM_DEFAUT = 13;

	/**
	 * Six décimales : environ onze centimètres. Au-delà, on stocke du bruit de saisie, et les
	 * coordonnées deviennent illisibles dans le formulaire.
	 */
	public const DECIMALES = 6;

	/**
	 * Une coordonnée valide, ou NULL.
	 *
	 * Accepte la virgule décimale : un administrateur francophone qui copie « 48,8566 » depuis un
	 * tableur ne doit pas voir son lieu partir à l'équateur. Refuse en revanche tout ce qui n'est pas
	 * un nombre — y compris la chaîne vide, qui vaut « non renseigné » et non « zéro ».
	 *
	 * @param mixed $valeur
	 */
	public static function coordonnee($valeur, float $min, float $max): ?float
	{
		if (is_string($valeur))
		{
			$valeur = str_replace(',', '.', trim($valeur));
		}

		if ($valeur === '' || $valeur === NULL || !is_numeric($valeur))
		{
			return NULL;
		}

		$nombre = (float) $valeur;

		if ($nombre < $min || $nombre > $max || !is_finite($nombre))
		{
			return NULL;
		}

		return round($nombre, self::DECIMALES);
	}

	/** @param mixed $valeur */
	public static function latitude($valeur): ?float
	{
		return self::coordonnee($valeur, self::LAT_MIN, self::LAT_MAX);
	}

	/** @param mixed $valeur */
	public static function longitude($valeur): ?float
	{
		return self::coordonnee($valeur, self::LON_MIN, self::LON_MAX);
	}

	/** @param mixed $valeur */
	public static function zoom($valeur): int
	{
		if (!is_numeric($valeur))
		{
			return self::ZOOM_DEFAUT;
		}

		return max(self::ZOOM_MIN, min(self::ZOOM_MAX, (int) $valeur));
	}

	/**
	 * Le centre et le zoom qui montrent tous les lieux donnés.
	 *
	 * Sans cela il faudrait choisir un centre à la main, et ajouter un lieu à l'autre bout du monde
	 * le ferait sortir de l'écran sans prévenir. Le zoom est déduit de l'étendue : plus les lieux
	 * sont dispersés, plus on prend de recul.
	 *
	 * @param  list<array{lat: float, lon: float}> $lieux
	 * @return array{lat: float, lon: float, zoom: int}
	 */
	public static function cadrage(array $lieux): array
	{
		if (!$lieux)
		{
			// Aucun lieu : le centre de l'Europe occidentale, au zoom le plus large. C'est arbitraire,
			// mais une carte doit bien montrer quelque chose.
			return ['lat' => 46.6, 'lon' => 2.5, 'zoom' => 5];
		}

		$lats = array_column($lieux, 'lat');
		$lons = array_column($lieux, 'lon');

		$lat_min = min($lats);
		$lat_max = max($lats);
		$lon_min = min($lons);
		$lon_max = max($lons);

		$centre = [
			'lat' => round(($lat_min + $lat_max) / 2, self::DECIMALES),
			'lon' => round(($lon_min + $lon_max) / 2, self::DECIMALES),
		];

		// L'étendue la plus grande des deux décide : une file de lieux est-ouest se cadre sur la
		// longitude, une file nord-sud sur la latitude.
		$etendue = max($lat_max - $lat_min, $lon_max - $lon_min);

		return $centre + ['zoom' => self::zoom_pour($etendue)];
	}

	/**
	 * Le zoom qui fait tenir une étendue donnée, en degrés.
	 *
	 * Le palier double à chaque cran, comme le zoom lui-même : c'est la règle des tuiles, où passer
	 * d'un niveau au suivant divise par deux ce qu'on voit.
	 */
	public static function zoom_pour(float $etendue): int
	{
		if ($etendue <= 0)
		{
			// Un seul lieu, ou plusieurs au même endroit : on peut serrer.
			return 15;
		}

		// Une LISTE de paires, et non un tableau associatif : en PHP une clé de tableau ne peut pas
		// être un nombre à virgule — `[0.02 => 14, 0.05 => 13]` écrase tout sur la clé entière 0, et
		// la table de paliers ne contient plus qu'une seule entrée. C'est le test unitaire qui l'a
		// montré ; à la lecture, le tableau avait l'air juste.
		foreach ([[0.02, 14], [0.05, 13], [0.1, 12], [0.25, 11], [0.5, 10], [1.0, 9], [2.0, 8],
		          [5.0, 7], [10.0, 6], [20.0, 5], [45.0, 4], [90.0, 3], [180.0, 2]] as [$palier, $zoom])
		{
			if ($etendue <= $palier)
			{
				return $zoom;
			}
		}

		return self::ZOOM_MIN;
	}

	/**
	 * Le lien « voir sur OpenStreetMap » d'un lieu.
	 *
	 * On le construit nous-mêmes plutôt que de laisser saisir une adresse : une adresse saisie est
	 * une adresse à valider, et celle-ci est toujours juste par construction.
	 */
	public static function lien_osm(float $lat, float $lon, int $zoom = self::ZOOM_DEFAUT): string
	{
		return sprintf(
			'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=%d/%s/%s',
			self::nombre($lat), self::nombre($lon), self::zoom($zoom), self::nombre($lat), self::nombre($lon)
		);
	}

	/**
	 * Un nombre écrit pour une machine : point décimal, jamais de notation exponentielle.
	 *
	 * `(string) 0.0000001` rend « 1.0E-7 », que ni une URL ni JavaScript ne relisent comme on
	 * l'espère. Et sous une locale francophone, `printf('%f')` mettrait une virgule.
	 */
	public static function nombre(float $valeur): string
	{
		return rtrim(rtrim(number_format($valeur, self::DECIMALES, '.', ''), '0'), '.') ?: '0';
	}

	/**
	 * Une couleur qu'on accepte d'écrire dans un `style`.
	 *
	 * Elle part dans un attribut `data-`, puis le script la pose sur le marqueur. Une valeur libre
	 * y serait une injection CSS — `red;background:url(//ailleurs)` suffit à faire sortir une
	 * requête vers un tiers. Seule la notation hexadécimale passe, et une chaîne vide vaut « la
	 * couleur du thème », que la feuille décide.
	 *
	 * @param mixed $valeur
	 */
	public static function couleur($valeur): string
	{
		if (!is_string($valeur))
		{
			return '';
		}

		$valeur = trim($valeur);

		return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $valeur) ? strtolower($valeur) : '';
	}

	/**
	 * Une adresse qu'on accepte d'écrire dans un `href`.
	 *
	 * Seuls `http` et `https` passent : `javascript:` saisi en administration atterrirait tel quel
	 * dans le lien du lieu sur la page publique.
	 *
	 * @param mixed $url
	 */
	public static function lien_sur($url): string
	{
		if (!is_string($url))
		{
			return '';
		}

		$url = trim($url);

		return $url !== '' && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== FALSE ? $url : '';
	}
}
