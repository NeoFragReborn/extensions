<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des citations, datée de la dernière modifiée.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Quotes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'quotes', 'date' => $this->db->select('MAX(updated_at)')->from('nf_quotes')->where('published', '1')->row()]];
	}
}
