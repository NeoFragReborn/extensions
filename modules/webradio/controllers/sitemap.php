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
		$emissions = $this->db->select('COUNT(*) AS nombre', 'MAX(updated_at) AS date')->from('nf_webradio_shows')->where('published', '1')->row();

		// Ni flux ni émission : la page ne dirait que « rien pour l'instant », une page vide (« soft 404 »)
		// pour Google — la vitrine en annonçait six (2026-10-07).
		if (!(int) ($emissions['nombre'] ?? 0) && \NF\Modules\Webradio\Lib\Schedule::flux(NeoFrag()->config->webradio_stream) === '')
		{
			return [];
		}

		return [['adresse' => 'webradio', 'date' => $emissions['date'] ?? NULL]];
	}
}
