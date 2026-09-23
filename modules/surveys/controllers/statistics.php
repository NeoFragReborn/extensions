<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Surveys\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'surveys' => [
				'title' => $this->lang('Sondages'),
				'data'  => function(){
					$this->db	->from('nf_surveys')
								->where('published', 1);

					return 'created_at';
				}
			]
		];
	}
}
