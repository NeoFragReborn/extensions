<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des liens, datée du dernier lien publié. Les
 * adresses `links/go/<id>` comptent un clic puis redirigent : elles n'ont rien à faire dans un plan.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Links\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'links', 'date' => $this->db->select('MAX(created_at)')->from('nf_links')->where('published', '1')->row()]];
	}
}
