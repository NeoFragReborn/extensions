<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Classifieds\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'classifieds' => [
				'title' => $this->lang('Petites annonces'),
				'data'  => function(){
					$this->db	->from('nf_classifieds')
								->where('status', 'published');

					return 'created_at';
				}
			]
		];
	}
}
