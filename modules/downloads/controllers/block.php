<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Downloads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Block extends Controller_Module
{
	public function block()
	{
		return [
			'downloads.popular' => [
				'title'  => $this->lang('Téléchargements populaires'),
				'render' => function(){
					$rows = $this->db	->select('id', 'title')
										->from('nf_downloads')
										->where('published', '1')
										->order_by('downloads_count DESC, title ASC')
										->limit(5)
										->get();

					if (!$rows)
					{
						return '';
					}

					$html = '<div class="nf-block nf-block-downloads"><ul class="list-group list-group-flush">';

					foreach ($rows as $f)
					{
						$html .= '<li class="list-group-item"><a href="'.url('downloads/go/'.$f['id']).'">'
							.icon('fas fa-download').' '.htmlspecialchars($f['title']).'</a></li>';
					}

					return $html.'</ul></div>';
				}
			]
		];
	}
}
