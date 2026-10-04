<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Recettes — recettes classées, avec leurs ingrédients et leurs étapes.
 *
 * Deuxième des trois modules de contenu ajoutés le 2026-09-20, bâti sur le patron du module `quotes` :
 * des catégories d'un côté, des entrées de l'autre, une page publique et un écran d'administration
 * avec recherche, filtre et tri. Ce qui lui est propre tient en trois points :
 *
 *   - les **ingrédients** et les **étapes** sont saisis une par ligne, en texte brut. C'est la forme
 *     la plus simple à saisir, la plus simple à relire, et elle se rend en liste numérotée sans
 *     jamais avoir à accepter du HTML ;
 *   - les **durées** et le **nombre de parts** sont des entiers bornés, affichés seulement s'ils sont
 *     renseignés — une recette sans temps de cuisson ne doit pas afficher « 0 min » ;
 *   - la page publique porte un balisage `schema.org/Recipe`, qui est ce que les moteurs de
 *     recherche lisent pour présenter une recette dans leurs résultats.
 */

namespace NF\Modules\Recipes;

use NF\NeoFrag\Addons\Module;

class Recipes extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Recettes'),
			'description' => $this->lang('Recueil de recettes classées, avec leurs ingrédients, leurs étapes et leurs durées.'),
			'icon'        => 'fas fa-utensils',
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
				'{id}/{url_title}'                  => '_recette',
				'admin{pages}'                      => 'index',
				'admin/r/add'                       => '_r_add',
				'admin/r/{id}/{url_title}'          => '_r_edit',
				'admin/r/delete/{id}/{url_title}'   => '_r_delete',
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
						'title'  => $this->lang('Recettes'),
						'icon'   => 'fas fa-utensils',
						'access' => [
							'manage_recipes'    => ['title' => $this->lang('Gérer les recettes'), 'icon' => 'fas fa-utensils', 'admin' => TRUE],
							'manage_categories' => ['title' => $this->lang('Gérer les catégories'), 'icon' => 'far fa-folder', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
