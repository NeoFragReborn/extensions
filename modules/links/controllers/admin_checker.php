<?php
namespace NF\Modules\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'c.sort_order', 'COUNT(l.id) AS nb')
								->from('nf_links_categories c')
								->join('nf_links l', 'c.id = l.category_id', 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC')
								->get();

		$cat_ids = [];
		foreach ($cats as $c) { $cat_ids[(int)$c['id']] = TRUE; }

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($cat_ids[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('l.*', 'c.title AS cat_title')
							->from('nf_links l')
							->join('nf_links_categories c', 'l.category_id = c.id');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('l.title LIKE', $like, 'OR', 'l.url LIKE', $like);
		}
		if ($filters['category'])
		{
			$db->where('l.category_id', $filters['category']);
		}
		if ($filters['status'] === 'published')
		{
			$db->where('l.published', 1);
		}
		else if ($filters['status'] === 'draft')
		{
			$db->where('l.published', 0);
		}

		$links = $db->order_by('c.sort_order ASC, l.sort_order ASC')->limit(self::SEARCH_CAP)->get();

		$pub = 0;
		foreach ($links as $l) { if (!empty($l['published'])) $pub++; }

		$filters['matched']   = count($links);
		$filters['published'] = $pub;
		$filters['drafts']    = count($links) - $pub;
		$filters['active']    = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		$filters['sort_cols'] = [
			'date'   => $this->lang('Date'),
			'title'  => $this->lang('Titre'),
			'clicks' => $this->lang('Clics')
		];
		list($links, $filters['sort']) = $this->sort_items($links, [
			'date'   => 'created_at',
			'title'  => 'title',
			'clicks' => 'clicks'
		], 'date', 'desc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($links, $page), $filters];
	}

	public function _link_add() { return [NULL]; }
	public function _link_edit($id, $title)
	{
		$l = NeoFrag()->db->select('*')->from('nf_links')->where('id', $id)->row();
		return $l ? [$l] : NULL;
	}
	public function _link_delete($id, $title)
	{
		$l = NeoFrag()->db->select('id', 'title')->from('nf_links')->where('id', $id)->row();
		return $l ? [$l] : NULL;
	}
	public function _cat_add() { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_links_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_links_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
