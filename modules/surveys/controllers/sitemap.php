<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les sondages publiés, clos compris — un sondage clos
 * montre ses résultats. Monolingue, sans droit de lecture.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Surveys\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [['adresse' => 'surveys']];

		foreach ($this->db->select('id', 'title', 'created_at')->from('nf_surveys')->where('published', '1')->order_by('created_at DESC')->get() as $sondage)
		{
			$adresses[] = ['adresse' => 'surveys/'.$sondage['id'].'/'.url_title($sondage['title']), 'date' => $sondage['created_at'], 'sans_langue' => TRUE];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
