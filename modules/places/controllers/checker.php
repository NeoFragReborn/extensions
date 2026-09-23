<?php
declare(strict_types=1);
namespace NF\Modules\Places\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Places\Lib\Place;

class Checker extends Module_Checker
{
	public function index()
	{
		$lieux = NeoFrag()->db	->select('p.id', 'p.title', 'p.description', 'p.address', 'p.latitude', 'p.longitude', 'p.link',
									'c.title AS cat_title', 'c.icon AS cat_icon', 'c.color AS cat_color')
								->from('nf_places p')
								->join('nf_places_categories c', 'p.category_id = c.id')
								->where('p.published', '1')
								->order_by('c.sort_order ASC, p.sort_order ASC, p.title ASC')
								->get();

		$propres = [];

		foreach ($lieux as $lieu)
		{
			// Une ligne dont les coordonnées ne sont pas exploitables n'est pas montrée : elle
			// déplacerait la carte sans que personne comprenne pourquoi. Le cas ne devrait pas se
			// produire — le formulaire les valide — mais une base peut être modifiée à la main.
			$lat = Place::latitude($lieu['latitude']);
			$lon = Place::longitude($lieu['longitude']);

			if ($lat === NULL || $lon === NULL)
			{
				continue;
			}

			$lieu['lat'] = $lat;
			$lieu['lon'] = $lon;
			$propres[]   = $lieu;
		}

		return [$propres, Place::cadrage($propres)];
	}
}
