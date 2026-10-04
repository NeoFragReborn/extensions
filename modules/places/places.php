<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Carte des lieux — des lieux situés sur une carte OpenStreetMap.
 *
 * Quatrième des six addons ajoutés le 2026-09-20, et celui qui portait le point d'attention : « notre
 * politique de sécurité est stricte et sans CDN ; la bibliothèque et ses tuiles doivent être servies
 * par nous ou explicitement autorisées, sans quoi rien ne s'affiche ».
 *
 * Vérification faite, la politique en place n'a demandé AUCUNE modification :
 *
 *   - les **tuiles** sont des images servies en HTTPS, et `img-src 'self' data: https:` les autorise
 *     déjà. Rien à ouvrir, donc rien à élargir ;
 *   - la **bibliothèque** est auto-hébergée dans cet addon (`js/leaflet.min.js`, `css/leaflet.css`,
 *     Leaflet 1.9.4, BSD 2-Clause, cf. `LICENSE-leaflet.txt`), ce que `script-src 'self'` et
 *     `style-src 'self'` couvrent. Les deux fichiers ont été récupérés depuis unpkg ET cdnjs et
 *     comparés : identiques octet pour octet ;
 *   - **aucune image de Leaflet n'est embarquée**. Ses trois images ne servent qu'au contrôle de
 *     couches et à son heuristique de chemin d'icône, dont on n'emploie ni l'un ni l'autre : les
 *     marqueurs sont des `divIcon` portant une icône FontAwesome, déjà présente dans le produit.
 *
 * La page reste utilisable **sans JavaScript** : la liste des lieux, leurs adresses et leurs liens
 * vers OpenStreetMap sont dans le HTML. La carte s'ajoute par-dessus si le script se charge.
 */

namespace NF\Modules\Places;

use NF\NeoFrag\Addons\Module;

class Places extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Carte des lieux'),
			'description' => $this->lang('Des lieux situés sur une carte OpenStreetMap, avec leur adresse et leur description.'),
			'icon'        => 'fas fa-map-marked-alt',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                  => 'index',
				'admin{pages}'                      => 'index',
				'admin/p/add'                       => '_p_add',
				'admin/p/{id}/{url_title}'          => '_p_edit',
				'admin/p/delete/{id}/{url_title}'   => '_p_delete',
				'admin/cat/add'                     => '_cat_add',
				'admin/cat/{id}/{url_title}'        => '_cat_edit',
				'admin/cat/delete/{id}/{url_title}' => '_cat_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Carte des lieux'),
						'icon'   => 'fas fa-map-marked-alt',
						'access' => [
							'manage_places'     => ['title' => $this->lang('Gérer les lieux'), 'icon' => 'fas fa-map-marker-alt', 'admin' => TRUE],
							'manage_categories' => ['title' => $this->lang('Gérer les catégories'), 'icon' => 'far fa-folder', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
