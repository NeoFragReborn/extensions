<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Links\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'links' => [
				'title' => $this->lang('Liens'),
				'data'  => function(){
					$this->db	->from('nf_links')
								->where('published', 1);

					return 'created_at';
				}
			]
		];
	}
}
