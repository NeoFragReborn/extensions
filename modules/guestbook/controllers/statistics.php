<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Guestbook\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'guestbook' => [
				'title' => $this->lang('Livre d\'or'),
				'data'  => function(){
					$this->db->from('nf_guestbook');

					return 'created_at';
				}
			]
		];
	}
}
