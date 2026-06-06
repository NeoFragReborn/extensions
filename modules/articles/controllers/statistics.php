<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'articles' => [
				'title' => $this->lang('Articles'),
				'data'  => function(){
					$this->db	->from('nf_articles')
								->where('published', '1')
								->where('deleted_at IS NULL');

					return 'date';
				}
			]
		];
	}
}
