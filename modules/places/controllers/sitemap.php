<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des lieux, datée du dernier lieu modifié.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Places\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'places', 'date' => $this->db->select('MAX(updated_at)')->from('nf_places')->where('published', '1')->row()]];
	}
}
