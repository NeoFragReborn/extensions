<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le livre de recettes et chaque recette publiée.
 * Monolingue, sans droit de lecture.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Recipes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];

		foreach ($this->db->select('id', 'title', 'updated_at')->from('nf_recipes')->where('published', '1')->order_by('sort_order', 'id')->get() as $recette)
		{
			$adresses[] = ['adresse' => 'recipes/'.$recette['id'].'/'.url_title($recette['title']), 'date' => $recette['updated_at']];
		}

		array_unshift($adresses, ['adresse' => 'recipes', 'date' => $adresses ? max(array_column($adresses, 'date')) : NULL]);

		return $adresses;
	}
}
