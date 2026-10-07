<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le livre d'or.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Guestbook\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		// Sans message publié, la page ne dirait que « rien pour l'instant » : une page vide (« soft 404 »)
		// pour Google — la vitrine en annonçait six (2026-10-07).
		if (!(int) $this->db->select('COUNT(*)')->from('nf_guestbook')->where('status', 'approved')->row())
		{
			return [];
		}

		return [['adresse' => 'guestbook']];
	}
}
