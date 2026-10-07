<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les petites annonces publiées et les catégories qui en
 * ont. Une annonce en attente, refusée ou close n'y figure pas : les listes du module ne la montrent pas
 * non plus. Monolingue.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Classifieds\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses   = [];
		$categories = [];

		foreach ($this->db->select('id', 'category_id', 'title', 'updated_at')->from('nf_classifieds')->where('status', 'published')->order_by('updated_at DESC')->get() as $annonce)
		{
			$adresses[] = ['adresse' => 'classifieds/'.$annonce['id'].'/'.url_title($annonce['title']), 'date' => $annonce['updated_at'], 'sans_langue' => TRUE];

			if ($annonce['category_id'])
			{
				$categories[(int) $annonce['category_id']] = max($categories[(int) $annonce['category_id']] ?? '', (string) $annonce['updated_at']);
			}
		}

		if ($categories)
		{
			foreach ($this->db->select('id', 'title')->from('nf_classifieds_categories')->where('id', array_keys($categories))->get() as $categorie)
			{
				$adresses[] = ['adresse' => 'classifieds/category/'.$categorie['id'].'/'.url_title($categorie['title']), 'date' => $categories[(int) $categorie['id']]];
			}
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!$adresses)
		{
			return [];
		}

		array_unshift($adresses, ['adresse' => 'classifieds', 'date' => $adresses[0]['date'] ?? NULL]);

		return $adresses;
	}
}
