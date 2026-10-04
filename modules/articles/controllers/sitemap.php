<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le Blog, ses billets parus et ses catégories qui en
 * ont, dans la langue du plan. Les chemins sont ceux du module (`articles/…`) : url() les écrit sous
 * l'adresse publique du Blog, `/fr/blog/…` (cf. Url::ADRESSE_DE_MODULE).
 *
 * Un billet rédigé dans une autre langue n'y figure pas : servi ici en repli, il se déclare canonique
 * dans la sienne. Appelé par Settings\Controllers\Ajax::sitemap().
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$langue     = $this->config->lang->info()->name;
		$maintenant = date('Y-m-d H:i:s');
		$adresses   = [];
		$categories = [];

		foreach ($this->db	->select('a.article_id', 'a.category_id', 'al.title', 'a.date')
							->from('nf_articles a')
							->join('nf_articles_lang al', 'al.article_id = a.article_id')
							->where('al.lang', $langue)
							->where('a.published', '1')
							->where('a.deleted_at', NULL)
							->where('a.date <=', $maintenant)
							->order_by('a.date DESC')
							->get() as $billet)
		{
			$adresses[] = ['adresse' => 'articles/'.$billet['article_id'].'/'.url_title($billet['title']), 'date' => $billet['date']];

			if ($billet['category_id'])
			{
				$categories[(int) $billet['category_id']] = max($categories[(int) $billet['category_id']] ?? '', (string) $billet['date']);
			}
		}

		if ($categories)
		{
			foreach ($this->db->select('category_id', 'name')->from('nf_articles_categories')->where('category_id', array_keys($categories))->get() as $categorie)
			{
				$adresses[] = ['adresse' => 'articles/category/'.$categorie['category_id'].'/'.url_title($categorie['name']), 'date' => $categories[(int) $categorie['category_id']]];
			}
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!$adresses)
		{
			return [];
		}

		array_unshift($adresses, ['adresse' => 'articles', 'date' => $adresses[0]['date'] ?? NULL]);

		return $adresses;
	}
}
