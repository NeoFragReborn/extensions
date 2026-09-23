<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Lib;

/**
 * Le cache du lecteur de flux : un fichier JSON par adresse, dans `cache/widget_rss/`.
 *
 * Même forme que le cache du widget `twitch` — un cache positif avec péremption, et un cache
 * NÉGATIF après un échec. Le cache négatif est ce qui évite qu'un flux tombé fasse retenter le
 * réseau à chaque affichage de la page : sans lui, un site mort coûte le délai d'attente complet à
 * chaque visiteur.
 *
 * La différence avec `twitch` est délibérée : ici le contenu périmé est **conservé et servi**. Un
 * article d'hier vaut mieux qu'un bloc vide, et surtout personne n'attend le réseau pour l'obtenir.
 * C'est ce qui tient la promesse de la fiche : un flux lent ne retarde jamais une page.
 *
 * Classe PURE au sens du produit : elle ne touche que le système de fichiers, ni base ni service
 * locator, et son dossier lui est donné — donc éprouvable dans un dossier temporaire.
 */
class Cache
{
	/** Après un échec, on ne retente pas avant ce délai. */
	public const ECHEC_TTL = 600;

	private string $dossier;

	public function __construct(string $dossier)
	{
		$this->dossier = rtrim($dossier, '/\\');
	}

	/**
	 * Ce que le cache contient pour cette adresse, périmé ou non.
	 *
	 * @return array{items: list<array<string, mixed>>, title: string, fetched_at: int, fresh: bool}|null
	 */
	public function lire(string $url, int $ttl): ?array
	{
		$fichier = $this->fichier($url, 'json');

		if (!is_file($fichier) || ($brut = @file_get_contents($fichier)) === FALSE)
		{
			return NULL;
		}

		$donnees = @json_decode($brut, TRUE);

		if (!is_array($donnees) || !isset($donnees['items']) || !is_array($donnees['items']))
		{
			return NULL;
		}

		$depuis = (int) ($donnees['fetched_at'] ?? 0);

		return [
			'items'      => array_values($donnees['items']),
			'title'      => (string) ($donnees['title'] ?? ''),
			'fetched_at' => $depuis,
			'fresh'      => $depuis > 0 && (time() - $depuis) < $ttl,
		];
	}

	/** @param list<array<string, mixed>> $articles */
	public function ecrire(string $url, array $articles, string $titre): bool
	{
		if (!$this->dossier())
		{
			return FALSE;
		}

		@unlink($this->fichier($url, 'fail'));

		$json = json_encode([
			'fetched_at' => time(),
			'title'      => $titre,
			'items'      => $articles,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $json !== FALSE && @file_put_contents($this->fichier($url, 'json'), $json) !== FALSE;
	}

	/** Note l'échec, pour ne pas retenter le réseau à chaque affichage. */
	public function marquer_echec(string $url): void
	{
		if ($this->dossier())
		{
			@touch($this->fichier($url, 'fail'));
		}
	}

	/** Vrai tant que le dernier échec est trop récent pour qu'on retente. */
	public function en_echec(string $url): bool
	{
		$fichier = $this->fichier($url, 'fail');

		return is_file($fichier) && (time() - (int) @filemtime($fichier)) < self::ECHEC_TTL;
	}

	private function fichier(string $url, string $extension): string
	{
		return $this->dossier.'/'.Settings::cle($url).'.'.$extension;
	}

	private function dossier(): bool
	{
		return is_dir($this->dossier) || @mkdir($this->dossier, 0775, TRUE) || is_dir($this->dossier);
	}
}
