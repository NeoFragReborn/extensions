<?php
/**
 * https://neofr.ag
 * Module Surveys — sondages publics avec choix unique ou multiple, résultats configurables.
 */

namespace NF\Modules\Surveys;

use NF\NeoFrag\Addons\Module;

class Surveys extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Sondages'),
			'description' => $this->lang('Sondages publics à choix unique ou multiple avec résultats configurables.'),
			'icon'        => 'fas fa-poll',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                  => 'index',
				'{id}/{url_title}'                  => '_show',
				'vote/{id}/{url_title}'             => '_vote',
				'admin{pages}'                      => 'index',
				'admin/add'                         => '_add',
				'admin/{id}/{url_title}'            => '_edit',
				'admin/delete/{id}/{url_title}'     => '_delete',
				'admin/close/{id}/{url_title}'      => '_close'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Sondages',
						'icon'   => 'fas fa-poll',
						'access' => [
							'manage' => ['title' => 'Gérer sondages', 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Hash de l'IP (pour anti-double-vote sans stocker l'IP en clair, RGPD-friendly).
	 */
	public static function ip_hash()
	{
		$ip = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
		return hash('sha256', 'survey-salt:'.$ip);
	}
}
