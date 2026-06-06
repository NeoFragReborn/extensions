<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Bugtracker\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'bug_tickets' => [
				'title' => $this->lang('Tickets'),
				'data'  => function(){
					$this->db->from('nf_bug_tickets');

					return 'created_at';
				}
			]
		];
	}
}
