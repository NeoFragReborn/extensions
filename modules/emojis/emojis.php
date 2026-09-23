<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Emojis — emojis personnalisés uploadés en admin, rendus partout via `:nom:` → <img>
 * (branché dans le helper central bbcode()). Set partagé par tout le contenu (forum, talks, news…).
 */

namespace NF\Modules\Emojis;

use NF\NeoFrag\Addons\Module;

class Emojis extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Emojis'),
			'description' => $this->lang('Emojis personnalisés (`:nom:`) uploadés en admin, rendus dans tout le contenu.'),
			'icon'        => 'far fa-smile',
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
				'admin{pages}'                  => 'index',
				'admin/add'                     => '_add',
				'admin/delete/{id}/{url_title}' => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Emojis'),
						'icon'   => 'far fa-smile',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les emojis'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
