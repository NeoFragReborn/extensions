<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le suivi des bugs et chaque ticket — tous publics,
 * comme dans le module. Un ticket est la réponse que cherche quelqu'un qui tape son erreur dans un
 * moteur. Monolingue.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Bugtracker\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];

		foreach ($this->db->select('id', 'title', 'updated_at')->from('nf_bug_tickets')->order_by('updated_at DESC')->get() as $ticket)
		{
			$adresses[] = ['adresse' => 'bugtracker/'.$ticket['id'].'/'.url_title($ticket['title']), 'date' => $ticket['updated_at'], 'sans_langue' => TRUE];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!$adresses)
		{
			return [];
		}

		array_unshift($adresses, ['adresse' => 'bugtracker', 'date' => $adresses[0]['date'] ?? NULL]);

		return $adresses;
	}
}
