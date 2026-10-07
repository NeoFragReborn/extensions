<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des téléchargements, datée du dernier fichier
 * publié. Les adresses `downloads/go/<id>` comptent un clic puis redirigent : elles n'ont rien à faire
 * dans un plan.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Downloads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!(int) $this->db->select('COUNT(*)')->from('nf_downloads')->where('published', '1')->row())
		{
			return [];
		}

		return [['adresse' => 'downloads', 'date' => $this->db->select('MAX(created_at)')->from('nf_downloads')->where('published', '1')->row(), 'sans_langue' => TRUE]];
	}
}
