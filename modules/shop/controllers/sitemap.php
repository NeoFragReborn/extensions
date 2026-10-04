<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la boutique, datée de son dernier article en vente.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Shop\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!(int) $this->db->select('COUNT(*)')->from('nf_shop_items')->where('active', '1')->row())
		{
			return [];
		}

		return [['adresse' => 'shop', 'date' => $this->db->select('MAX(created_at)')->from('nf_shop_items')->where('active', '1')->row()]];
	}
}
