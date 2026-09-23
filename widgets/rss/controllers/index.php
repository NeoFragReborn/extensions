<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use NF\Widgets\Rss\Lib\Cache;
use NF\Widgets\Rss\Lib\Feed;
use NF\Widgets\Rss\Lib\Settings;

class Index extends Controller_Widget
{
	/** Le cache vit là où ceux des autres widgets vivent déjà. */
	public const CACHE_DIR = 'cache/widget_rss';

	/**
	 * Au premier affichage, quand rien n'est encore en cache, on s'autorise UNE requête, très
	 * courte. Sans elle le widget resterait vide jusqu'au premier passage du cron — ce qui
	 * donnerait l'impression qu'il ne marche pas.
	 */
	public const DELAI_A_FROID = 3;

	public function index($settings = [])
	{
		$reglages = Settings::normaliser(is_array($settings) ? $settings : []);

		if ($reglages['url'] === '')
		{
			// Pas d'adresse : rien à montrer au visiteur. L'administrateur, lui, est prévenu dans
			// le formulaire du widget — inutile d'afficher un bloc d'erreur sur le site public.
			return '';
		}

		$cache   = new Cache(self::CACHE_DIR);
		$en_boite = $cache->lire($reglages['url'], $reglages['ttl']);

		// Le cas qui fait toute la différence : un cache PÉRIMÉ est servi tel quel. Le visiteur
		// n'attend jamais le réseau ; c'est le cron qui rafraîchira.
		if ($en_boite === NULL && !$cache->en_echec($reglages['url']))
		{
			$en_boite = self::rafraichir($cache, $reglages, self::DELAI_A_FROID);
		}

		if ($en_boite === NULL || !$en_boite['items'])
		{
			return '';
		}

		return $this	->css('rss')
						->view('index', [
							'titre'    => $reglages['title'] !== '' ? $reglages['title'] : $en_boite['title'],
							// Le cache garde le maximum d'articles ; c'est ici qu'on n'en montre que
							// le nombre demandé, sans avoir à ressortir sur le réseau.
							'articles' => array_slice($en_boite['items'], 0, $reglages['count']),
							'lien'     => $reglages['url'],
							'avec_date'   => $reglages['show_date'],
							'avec_resume' => $reglages['show_summary'],
							'depuis'   => $en_boite['fetched_at'],
						]);
	}

	/**
	 * Va chercher le flux, le range, et rend ce qui vient d'être rangé.
	 *
	 * Partagée avec `controllers/cron.php` : le rafraîchissement doit être EXACTEMENT le même qu'il
	 * vienne d'une page à froid ou du cron, sinon les deux chemins divergent et l'un des deux finit
	 * par n'être jamais éprouvé.
	 *
	 * @param  array{url: string, title: string, count: int, ttl: int, show_date: bool, show_summary: bool} $reglages
	 * @return array{items: list<array<string, mixed>>, title: string, fetched_at: int, fresh: bool}|null
	 */
	public static function rafraichir(Cache $cache, array $reglages, int $delai): ?array
	{
		$xml = nf_fetch_public_url($reglages['url'], $delai, 1048576, 'NeoFrag-Rss/1.0');

		if ($xml === NULL)
		{
			$cache->marquer_echec($reglages['url']);
			return NULL;
		}

		// On range plus d'articles que demandé : changer le réglage « nombre d'articles » ne doit
		// pas obliger à ressortir sur le réseau.
		$articles = Feed::parse($xml, Settings::ARTICLES_MAX);

		if (!$articles)
		{
			$cache->marquer_echec($reglages['url']);
			return NULL;
		}

		$cache->ecrire($reglages['url'], $articles, Feed::titre($xml));

		/*
		 * On rend ce qu'on vient de LIRE SUR LE RÉSEAU, et non ce que le cache veut bien nous
		 * relire. La version précédente renvoyait `$cache->lire(…)` : quand l'écriture échouait —
		 * dossier de cache non créable, disque plein, droits — le widget affichait le VIDE alors
		 * qu'il avait les articles en main, et sans un mot nulle part. Un cache est une
		 * accélération, jamais une condition pour afficher.
		 */
		return [
			'items'      => $articles,
			'title'      => Feed::titre($xml),
			'fetched_at' => time(),
			'fresh'      => TRUE,
		];
	}
}
