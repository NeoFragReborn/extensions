<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Activity extends Controller_Module
{
	public function activity($user_id, $limit)
	{
		$items = [];

		foreach ($this->db	->select('a.article_id', 'al.title', 'UNIX_TIMESTAMP(a.date) AS date')
							->from('nf_articles a')
							->join('nf_articles_lang al', 'al.article_id = a.article_id')
							->where('al.lang', $this->config->lang->info()->name)
							->where('a.user_id', $user_id)
							->where('a.published', '1')
							->where('a.deleted_at IS NULL')
							->order_by('a.date DESC')
							->limit($limit)
							->get() as $row)
		{
			$items[] = [
				'date'  => (int)$row['date'],
				'icon'  => 'far fa-file-alt',
				'type'  => 'articles',
				'title' => $row['title'],
				'url'   => 'articles/'.$row['article_id'].'/'.url_title($row['title'])
			];
		}

		return $items;
	}
}
