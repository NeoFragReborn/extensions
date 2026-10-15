<?php
declare(strict_types=1);
namespace NF\Modules\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('c.id', 'cl.title')
								->from('nf_links_categories c')
								->join_lang('nf_links_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
								->order_by('c.sort_order ASC, c.id ASC')
								->get();

		$groups = [];
		foreach ($cats as $c)
		{
			$links = NeoFrag()->db	->select('l.id', 'll.title', 'l.url', 'll.description', 'l.clicks')
									->from('nf_links l')
									->join_lang('nf_links_lang ll', 'link_id', 'l.id', NULL, 'LEFT')
									->where('l.category_id', $c['id'])
									->where('l.published', '1')
									->order_by('l.sort_order ASC, ll.title ASC')
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
