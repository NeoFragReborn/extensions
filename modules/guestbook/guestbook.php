<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Guestbook — livre d'or simple avec modération.
 */

namespace NF\Modules\Guestbook;

use NF\NeoFrag\Addons\Module;

class Guestbook extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Livre d\'or'),
			'description' => $this->lang('Livre d\'or avec modération admin et rate-limit anti-spam.'),
			'icon'        => 'far fa-comment-dots',
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
				''                            => 'index',
				'admin{pages}'                => 'index',
				'admin/{action}/{id}'         => '_action'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Livre d\'or'),
						'icon'   => 'far fa-comment-dots',
						'access' => [
							'moderate' => ['title' => $this->lang('Modérer les messages'), 'icon' => 'fas fa-gavel', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
