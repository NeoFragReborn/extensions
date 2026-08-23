<?php
namespace NF\Modules\Classifieds\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'c.sort_order', 'COUNT(a.id) AS nb')
								->from('nf_classifieds_categories c')
								->join('nf_classifieds a', 'c.id = a.category_id', 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC')
								->get();

		$cat_ids = [];
		foreach ($cats as $c) { $cat_ids[(int)$c['id']] = TRUE; }

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($cat_ids[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['pending', 'published', 'closed', 'rejected'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('a.*', 'c.title AS cat_title', 'u.username AS author', 'u.id AS author_id', 'UNIX_TIMESTAMP(a.created_at) AS created_ts')
							->from('nf_classifieds a')
							->join('nf_classifieds_categories c', 'a.category_id = c.id', 'LEFT')
							->join('nf_user u', 'a.user_id = u.id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$db->where('a.title LIKE', '%'.$filters['q'].'%');
		}
		if ($filters['category'])
		{
			$db->where('a.category_id', $filters['category']);
		}
		if ($filters['status'] !== '')
		{
			$db->where('a.status', $filters['status']);
		}

		$ads = $db->order_by("FIELD(a.status, 'pending','published','closed','rejected') ASC, a.created_at DESC")->limit(self::SEARCH_CAP)->get();

		$pending = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_classifieds')->where('status', 'pending')->row();

		$filters['matched'] = count($ads);
		$filters['pending'] = $pending;
		$filters['active']  = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		$filters['sort_cols'] = ['date' => $this->lang('Date'), 'title' => $this->lang('Titre'), 'views' => $this->lang('Vues'), 'price' => $this->lang('Prix')];
		list($ads, $filters['sort']) = $this->sort_items($ads, ['date' => 'created_ts', 'title' => 'title', 'views' => 'views', 'price' => 'price'], 'date', 'desc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($ads, $page), $filters];
	}

	public function _cat_add()  { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_classifieds_categories')->where('id', $id)->row(FALSE);
		return $c ? [$c] : NULL;
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_classifieds_categories')->where('id', $id)->row(FALSE);
		return $c ? [$c] : NULL;
	}

	public function _approve($id, $title) { return $this->_ad($id); }
	public function _reject($id, $title)  { return $this->_ad($id); }
	public function _delete($id, $title)  { return $this->_ad($id); }

	private function _ad($id)
	{
		$ad = NeoFrag()->db->select('id', 'title', 'user_id')->from('nf_classifieds')->where('id', $id)->row(FALSE);
		return $ad ? [$ad] : NULL;
	}
}
