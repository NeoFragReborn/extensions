<?php
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'c.sort_order', 'COUNT(f.id) AS nb')
								->from('nf_downloads_categories c')
								->join('nf_downloads f', 'c.id = f.category_id', 'LEFT')
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

		$db = NeoFrag()->db	->select('f.*', 'c.title AS cat_title')
							->from('nf_downloads f')
							->join('nf_downloads_categories c', 'f.category_id = c.id');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('f.title LIKE', $like, 'OR', 'f.version LIKE', $like);
		}
		if ($filters['category'])
		{
			$db->where('f.category_id', $filters['category']);
		}
		if ($filters['status'] === 'published')
		{
			$db->where('f.published', 1);
		}
		else if ($filters['status'] === 'draft')
		{
			$db->where('f.published', 0);
		}

		$files = $db->order_by('c.sort_order ASC, f.title ASC')->limit(self::SEARCH_CAP)->get();

		$pub = 0;
		foreach ($files as $f) { if (!empty($f['published'])) $pub++; }

		$filters['matched']   = count($files);
		$filters['published'] = $pub;
		$filters['drafts']    = count($files) - $pub;
		$filters['active']    = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($files, $page), $filters];
	}

	public function _file_add() { return [NULL]; }
	public function _file_edit($id, $title)
	{
		$f = NeoFrag()->db->select('*')->from('nf_downloads')->where('id', $id)->row();
		return $f ? [$f] : NULL;
	}
	public function _file_delete($id, $title)
	{
		$f = NeoFrag()->db->select('id', 'title')->from('nf_downloads')->where('id', $id)->row();
		return $f ? [$f] : NULL;
	}
	public function _cat_add() { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_downloads_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_downloads_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
