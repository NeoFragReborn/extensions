<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Dictionnaire — un lexique des termes de la communauté, rangé par lettre.
 *
 * Troisième des trois modules de contenu ajoutés le 2026-09-20, bâti sur le même patron que `quotes` et
 * `recipes` : des catégories d'un côté, des entrées de l'autre, une page publique et un écran
 * d'administration avec recherche, filtre et tri.
 *
 * Ce qui lui est propre : le rangement **par lettre**. La page publique s'ouvre sur un index
 * alphabétique, et un terme accentué se range sous sa lettre sans accent — « Éclaireur » sous E,
 * « ÆTHER » sous A, « 1v1 » sous #. La règle vit dans `lib/term.php`, classe pure et éprouvée, car
 * c'est la seule vraie difficulté du module.
 */

namespace NF\Modules\Glossary;

use NF\NeoFrag\Addons\Module;

class Glossary extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Dictionnaire'),
			'description' => $this->lang('Lexique des termes de la communauté, rangé par lettre, avec ses synonymes.'),
			'icon'        => 'fas fa-book',
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
				'admin/t/add'                       => '_t_add',
				'admin/t/{id}/{url_title}'          => '_t_edit',
				'admin/t/delete/{id}/{url_title}'   => '_t_delete',
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
						'title'  => $this->lang('Dictionnaire'),
						'icon'   => 'fas fa-book',
						'access' => [
							'manage_terms'      => ['title' => $this->lang('Gérer les termes'), 'icon' => 'fas fa-book', 'admin' => TRUE],
							'manage_categories' => ['title' => $this->lang('Gérer les catégories'), 'icon' => 'far fa-folder', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
