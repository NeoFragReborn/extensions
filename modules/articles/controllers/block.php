<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Block extends Controller_Module
{
	public function block()
	{
		return [
			'articles.latest' => [
				'title'  => $this->lang('Derniers articles'),
				'fields' => [
					'count' => ['type' => 'int', 'default' => 5, 'min' => 1, 'max' => 20],
				],
				'render' => function($settings = []){
					$rows = $this->db	->select('a.article_id', 'al.title')
										->from('nf_articles a')
										->join('nf_articles_lang al', 'a.article_id = al.article_id')
										->where('a.published', '1')
										->where('a.deleted_at IS NULL')
										->where('a.date <=', date('Y-m-d H:i:s'))
										->where('al.lang', $this->config->lang->info()->name)
										->order_by('a.date DESC')
										->limit((int) ($settings['count'] ?? 5))
										->get();

					if (!$rows)
					{
						return '';
					}

					$html = '<div class="nf-block nf-block-articles"><ul class="list-group list-group-flush">';

					foreach ($rows as $a)
					{
						$html .= '<li class="list-group-item"><a href="'.url('articles/'.$a['article_id'].'/'.url_title($a['title'])).'">'
							.icon('far fa-file-alt').' '.htmlspecialchars((string) ($a['title'])).'</a></li>';
					}

					return $html.'</ul></div>';
				}
			]
		];
	}
}
