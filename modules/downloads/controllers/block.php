<?php
declare(strict_types=1);
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
				'fields' => [
					'count' => ['type' => 'int', 'default' => 5, 'min' => 1, 'max' => 20],
				],
				'render' => function($settings = []){
					$rows = $this->db	->select('d.id', 'dl.title')
										->from('nf_downloads d')
										->join_lang('nf_downloads_lang dl', 'download_id', 'd.id', NULL, 'LEFT')
										->where('d.published', '1')
										->order_by('d.downloads_count DESC, dl.title ASC')
										->limit((int) ($settings['count'] ?? 5))
										->get();

					if (!$rows)
					{
						return '';
					}

					$html = '<div class="nf-block nf-block-downloads"><ul class="list-group list-group-flush">';

					foreach ($rows as $f)
					{
						$html .= '<li class="list-group-item"><a href="'.url('downloads/go/'.$f['id']).'">'
							.icon('fas fa-download').' '.nf_texte($f['title']).'</a></li>';
					}

					return $html.'</ul></div>';
				}
			]
		];
	}
}
