<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page de la webradio et sa grille, datée de la
 * dernière émission modifiée.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Webradio\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'webradio', 'date' => $this->db->select('MAX(updated_at)')->from('nf_webradio_shows')->where('published', '1')->row()]];
	}
}
