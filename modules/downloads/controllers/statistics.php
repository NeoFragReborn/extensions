<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Downloads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'downloads' => [
				'title' => $this->lang('Téléchargements'),
				'data'  => function(){
					$this->db	->from('nf_downloads')
								->where('published', 1);

					return 'created_at';
				}
			]
		];
	}
}
