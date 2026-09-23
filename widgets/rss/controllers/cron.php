<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Rss\Lib\Cache;
use NF\Widgets\Rss\Lib\Settings;

/**
 * Rafraîchit le cache des flux, hors du rendu d'une page.
 *
 * C'est ce contrôleur qui tient la promesse de la « un flux lent ne doit jamais
 * retarder une page ». Le rendu, lui, ne fait que lire un fichier ; il sert même un contenu périmé
 * plutôt que d'attendre le réseau. Sans ce passage périodique, le cache finirait par vieillir
 * indéfiniment.
 *
 * Il est appelé par le carrefour `cron` de `/monitoring/cron`, comme les rappels du calendrier et
 * la parution du contenu programmé — NeoFrag n'a pas d'ordonnanceur interne, c'est un cron externe
 * qui frappe cette adresse.
 */
class Cron extends Controller
{
	/** Plus généreux qu'à froid : ici personne n'attend, et un flux lent mérite sa chance. */
	public const DELAI = 8;

	/** Borne du passage : un site aux vingt flux ne doit pas faire durer le cron indéfiniment. */
	public const FLUX_MAX = 20;

	/**
	 * Rend un compte rendu d'une ligne, dans la forme des autres lignes du cron.
	 *
	 * Ce qui est rafraîchi : les flux effectivement configurés dans des widgets posés sur le site,
	 * et seulement ceux dont le cache est périmé. Une adresse employée par plusieurs widgets n'est
	 * cherchée qu'une fois.
	 */
	public function cron(): string
	{
		$cache    = new Cache(Index::CACHE_DIR);
		$vus      = [];
		$rafraichis = 0;
		$echecs   = 0;

		foreach ($this->flux() as $reglages)
		{
			if (isset($vus[$reglages['url']]) || count($vus) >= self::FLUX_MAX)
			{
				continue;
			}

			$vus[$reglages['url']] = TRUE;

			$en_boite = $cache->lire($reglages['url'], $reglages['ttl']);

			if ($en_boite !== NULL && $en_boite['fresh'])
			{
				continue;
			}

			if (Index::rafraichir($cache, $reglages, self::DELAI) !== NULL)
			{
				$rafraichis++;
			}
			else
			{
				$echecs++;
			}
		}

		return $rafraichis.' refreshed, '.$echecs.' failed, '.count($vus).' feed(s)';
	}

	/**
	 * Les réglages de chaque widget `rss` posé sur le site.
	 *
	 * @return list<array{url: string, title: string, count: int, ttl: int, show_date: bool, show_summary: bool}>
	 */
	private function flux(): array
	{
		$flux = [];

		$lignes = $this	->db	->select('settings')
								->from('nf_widgets')
								->where('widget', 'rss')
								->get();

		foreach ((array) $lignes as $ligne)
		{
			$settings = @json_decode((string) $ligne, TRUE);

			if (!is_array($settings))
			{
				continue;
			}

			$reglages = Settings::normaliser($settings);

			if ($reglages['url'] !== '')
			{
				$flux[] = $reglages;
			}
		}

		return $flux;
	}
}
