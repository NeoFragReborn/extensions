<?php
declare(strict_types=1);
namespace NF\Modules\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('id', 'title')
								->from('nf_links_categories')
								->order_by('sort_order ASC, id ASC')
								->get();

		$groups = [];
		foreach ($cats as $c)
		{
			$links = NeoFrag()->db	->select('id', 'title', 'url', 'description', 'clicks')
									->from('nf_links')
									->where('category_id', $c['id'])
									->where('published', '1')
									->order_by('sort_order ASC, title ASC')
									->get();
			if (!empty($links))
			{
				$groups[] = ['cat' => $c, 'links' => $links];
			}
		}

		return [$groups];
	}

	public function _go($id)
	{
		$link = NeoFrag()->db	->select('id', 'url')
								->from('nf_links')
								->where('id', $id)
								->where('published', '1')
								->row();

		if (!empty($link))
		{
			NeoFrag()->db->execute('UPDATE nf_links SET clicks = clicks + 1 WHERE id = '.(int)$id);
			return [$link];
		}
	}
}
