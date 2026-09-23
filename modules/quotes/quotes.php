<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Citations — citations classées, avec leur auteur et leur source.
 *
 * Premier des trois modules de contenu de la il sert de patron aux deux autres
 * (recettes, dictionnaire). Le moule est celui de `faq` — des catégories d'un côté, des entrées de
 * l'autre, une page publique et un écran d'administration avec recherche, filtre et tri — parce
 * qu'il est déjà éprouvé et que trois modules bâtis pareil se maintiennent ensemble.
 *
 * Ce qui lui est propre : une citation est du TEXTE, pas du HTML. Là où `faq` ouvre un éditeur
 * riche pour sa réponse, on garde ici un simple champ de texte, échappé à l'affichage. Une citation
 * n'a pas besoin de mise en forme, et ne pas en offrir retire d'un coup toute la surface d'attaque
 * qui va avec.
 */

namespace NF\Modules\Quotes;

use NF\NeoFrag\Addons\Module;

class Quotes extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Citations'),
			'description' => $this->lang('Recueil de citations classées, avec leur auteur et leur source.'),
			'icon'        => 'fas fa-quote-right',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
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
				'admin/q/add'                       => '_q_add',
				'admin/q/{id}/{url_title}'          => '_q_edit',
				'admin/q/delete/{id}/{url_title}'   => '_q_delete',
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
						'title'  => $this->lang('Citations'),
						'icon'   => 'fas fa-quote-right',
						'access' => [
							'manage_quotes'     => ['title' => $this->lang('Gérer les citations'), 'icon' => 'fas fa-quote-right', 'admin' => TRUE],
							'manage_categories' => ['title' => $this->lang('Gérer les catégories'), 'icon' => 'far fa-folder',     'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
