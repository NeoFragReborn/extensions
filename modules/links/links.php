<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Links — annuaire de liens externes catégorisés.
 */

namespace NF\Modules\Links;

use NF\NeoFrag\Addons\Module;

class Links extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Annuaire de liens'),
			'description' => $this->lang('Annuaire de liens externes catégorisés avec redirection trackée et compteur de clics.'),
			'icon'        => 'fas fa-link',
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
				''                              => 'index',
				'go/{id}'                       => '_go',
				'admin{pages}'                  => 'index',
				'admin/link/add'                => '_link_add',
				'admin/link/{id}/{url_title}'   => '_link_edit',
				'admin/link/delete/{id}/{url_title}' => '_link_delete',
				'admin/cat/add'                 => '_cat_add',
				'admin/cat/{id}/{url_title}'    => '_cat_edit',
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
						'title'  => $this->lang('Liens'),
						'icon'   => 'fas fa-link',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer liens et catégories'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
