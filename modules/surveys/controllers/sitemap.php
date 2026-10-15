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

		// Le titre vit dans `nf_surveys_lang` : le lire ici rendrait NULL, et le plan du site tomberait
		// en avertissement PHP à chaque passage d'un robot (la leçon du palmarès, 2026-10-15).
		foreach ($this->db	->select('s.id', 'sl.title', 's.created_at')
							->from('nf_surveys s')
							->join_lang('nf_surveys_lang sl', 'survey_id', 's.id', NULL, 'LEFT')
							->where('s.published', '1')
							->order_by('s.created_at DESC')
							->get() as $sondage)
		{
			$adresses[] = ['adresse' => 'surveys/'.$sondage['id'].'/'.url_title($sondage['title']), 'date' => $sondage['created_at'], 'sans_langue' => TRUE];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
