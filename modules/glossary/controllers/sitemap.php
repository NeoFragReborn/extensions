<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page du glossaire, datée de son dernier terme
 * modifié. Les termes sont des ancres de cette page.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Glossary\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'glossary', 'date' => $this->db->select('MAX(updated_at)')->from('nf_glossary_terms')->where('published', '1')->row()]];
	}
}
