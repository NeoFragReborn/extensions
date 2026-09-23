<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Lib;

/**
 * Les réglages du lecteur de flux, normalisés.
 *
 * Classe PURE : aucune dépendance au service locator, donc éprouvable directement. Tout ce qui sort
 * d'ici part dans une requête réseau ou dans le HTML de la page ; rien n'est repris tel quel de la
 * base, et une valeur inconnue retombe sur le défaut plutôt que de faire échouer l'affichage.
 */
class Settings
{
	/** Bornes du nombre d'articles. Au-delà de vingt, ce n'est plus un widget mais une page. */
	public const ARTICLES_MIN = 1;
	public const ARTICLES_MAX = 20;

	/**
	 * Durées de cache proposées, en secondes.
	 *
	 * Le plus court est un quart d'heure : en dessous, on interrogerait un site tiers à chaque
	 * poignée de visites, ce qui est impoli et finit par un blocage.
	 */
	public const DUREES = [900, 1800, 3600, 10800, 21600, 86400];

	public const DUREE_DEFAUT = 3600;

	/**
	 * @param  array<string, mixed> $settings
	 * @return array{url: string, title: string, count: int, ttl: int, show_date: bool, show_summary: bool}
	 */
	public static function normaliser(array $settings): array
	{
		return [
			'url'          => self::url($settings['url'] ?? NULL),
			'title'        => self::ligne($settings['title'] ?? NULL, 80),
			'count'        => self::borne($settings['count'] ?? NULL, self::ARTICLES_MIN, self::ARTICLES_MAX, 5),
			'ttl'          => self::duree($settings['ttl'] ?? NULL),
			'show_date'    => self::booleen($settings['show_date'] ?? NULL, TRUE),
			'show_summary' => self::booleen($settings['show_summary'] ?? NULL, TRUE),
		];
	}

	/**
	 * L'adresse du flux, ou une chaîne vide.
	 *
	 * Seuls `http` et `https` sont acceptés. Le reste — `file://`, `gopher://`, `php://` — ne
	 * désigne pas un flux, mais une façon de faire lire au serveur ce qu'il ne devrait pas lire.
	 * La protection contre les adresses INTERNES, elle, n'est pas ici : elle se joue à la résolution
	 * du nom, dans `nf_fetch_public_url()`, puisqu'un nom public peut pointer une adresse privée.
	 *
	 * @param mixed $valeur
	 */
	public static function url($valeur): string
	{
		if (!is_string($valeur))
		{
			return '';
		}

		$valeur = trim($valeur);

		if ($valeur === '' || !preg_match('#^https?://#i', $valeur))
		{
			return '';
		}

		return filter_var($valeur, FILTER_VALIDATE_URL) !== FALSE ? $valeur : '';
	}

	/** Le nom du fichier de cache d'une adresse : stable, sans le moindre caractère de chemin. */
	public static function cle(string $url): string
	{
		return hash('sha256', $url);
	}

	/** @param mixed $valeur */
	private static function duree($valeur): int
	{
		$valeur = is_numeric($valeur) ? (int) $valeur : 0;

		return in_array($valeur, self::DUREES, TRUE) ? $valeur : self::DUREE_DEFAUT;
	}

	/** @param mixed $valeur */
	private static function borne($valeur, int $min, int $max, int $defaut): int
	{
		if (!is_numeric($valeur))
		{
			return $defaut;
		}

		return max($min, min($max, (int) $valeur));
	}

	/** @param mixed $valeur */
	private static function booleen($valeur, bool $defaut): bool
	{
		if ($valeur === NULL)
		{
			return $defaut;
		}

		return !in_array($valeur, ['0', 0, FALSE, '', 'false'], TRUE);
	}

	/** @param mixed $valeur */
	private static function ligne($valeur, int $max): string
	{
		if (!is_string($valeur))
		{
			return '';
		}

		$valeur = trim((string) preg_replace('/\s+/u', ' ', $valeur));

		return mb_strlen($valeur) > $max ? mb_substr($valeur, 0, $max) : $valeur;
	}
}
