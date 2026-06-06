<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Bugtracker\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Dashboard extends Controller_Module
{
	public function dashboard()
	{
		$critical = (int)$this->db	->from('nf_bug_tickets')
									->where('status', 'open')
									->where('priority', 'critical')
									->count();

		if ($critical <= 0)
		{
			return [];
		}

		return [[
			'title'  => $critical.' '.$this->lang('bug critique|bugs critiques', $critical),
			'action' => $this->lang('Voir les tickets'),
			'url'    => 'admin/bugtracker'
		]];
	}
}
