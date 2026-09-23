<?php
declare(strict_types=1);
namespace NF\Modules\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		if (!$this->is_authorized('moderate'))
		{
			$this->error->unauthorized();
		}

		$filters = [
			'q'      => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'status' => isset($_GET['status']) && in_array($_GET['status'], ['pending', 'approved', 'rejected'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('g.*', 'u.username', 'UNIX_TIMESTAMP(g.created_at) AS ts')
							->from('nf_guestbook g')
							->join('nf_user u', 'g.user_id = u.id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('g.message LIKE', $like, 'OR', 'g.name LIKE', $like);
		}

		if ($filters['status'] !== '')
		{
			$db->where('g.status', $filters['status']);
		}

		$msgs = $db->order_by('g.status ASC, g.created_at DESC')->limit(self::SEARCH_CAP)->get();

		// Compteurs globaux par statut (indépendants du filtre), pour le sous-titre.
		$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
		foreach (NeoFrag()->db->select('status', 'COUNT(*) AS n')->from('nf_guestbook')->group_by('status')->get() as $row)
		{
			if (isset($counts[$row['status']])) $counts[$row['status']] = (int)$row['n'];
		}

		$filters['matched'] = count($msgs);
		$filters['active']  = $filters['q'] !== '' || $filters['status'] !== '';

		$filters['sort_cols'] = [
			'date'   => $this->lang('Date'),
			'name'   => $this->lang('Auteur'),
			'status' => $this->lang('Statut')
		];
		list($msgs, $filters['sort']) = $this->sort_items($msgs, [
			'date'   => 'ts',
			'name'   => 'name',
			'status' => 'status'
		], 'date', 'desc');

		return [
			$this->module->pagination->fix_items_per_page(20)->get_data($msgs, $page),
			$filters,
			$counts
		];
	}

	public function _action($action, $id)
	{
		if (!$this->is_authorized('moderate'))
		{
			$this->error->unauthorized();
		}

		$valid_actions = ['approve', 'reject', 'delete'];
		if (!in_array($action, $valid_actions)) return;

		$msg = NeoFrag()->db->select('id')->from('nf_guestbook')->where('id', $id)->row();
		return $msg ? [$action, (int)$id] : NULL;
	}
}
