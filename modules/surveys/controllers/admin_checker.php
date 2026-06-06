<?php
namespace NF\Modules\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$filters = [
			'q'      => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'status' => isset($_GET['status']) && in_array($_GET['status'], ['draft', 'open', 'closed'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('s.*', 'COUNT(v.id) AS total_votes', 'COUNT(DISTINCT o.id) AS nb_options')
							->from('nf_surveys s')
							->join('nf_surveys_votes v', 's.id = v.survey_id', 'LEFT')
							->join('nf_surveys_options o', 's.id = o.survey_id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('s.title LIKE', $like, 'OR', 's.description LIKE', $like);
		}

		$surveys = $db->group_by('s.id')->order_by('s.created_at DESC')->limit(self::SEARCH_CAP)->get();

		// Statut dérivé (brouillon / ouvert / fermé), non stocké en colonne.
		$now = time();
		$derive = function($s) use ($now)
		{
			if (empty($s['published'])) return 'draft';
			if ($s['closed_at'] && strtotime($s['closed_at']) <= $now) return 'closed';
			return 'open';
		};

		$open = $closed = $drafts = 0;
		foreach ($surveys as $s)
		{
			$st = $derive($s);
			if ($st === 'open') $open++;
			elseif ($st === 'closed') $closed++;
			else $drafts++;
		}

		if ($filters['status'] !== '')
		{
			$surveys = array_values(array_filter($surveys, function($s) use ($derive, $filters)
			{
				return $derive($s) === $filters['status'];
			}));
		}

		$filters['matched'] = count($surveys);
		$filters['open']    = $open;
		$filters['closed']  = $closed;
		$filters['drafts']  = $drafts;
		$filters['active']  = $filters['q'] !== '' || $filters['status'] !== '';

		return [$this->module->pagination->fix_items_per_page(20)->get_data($surveys, $page), $filters];
	}

	public function _add() { return [NULL]; }
	public function _edit($id, $title)
	{
		$s = NeoFrag()->db->select('*')->from('nf_surveys')->where('id', $id)->row();
		return $s ? [$s] : NULL;
	}
	public function _delete($id, $title)
	{
		$s = NeoFrag()->db->select('id', 'title')->from('nf_surveys')->where('id', $id)->row();
		return $s ? [$s] : NULL;
	}
	public function _close($id, $title)
	{
		$s = NeoFrag()->db->select('id', 'title')->from('nf_surveys')->where('id', $id)->row();
		return $s ? [$s] : NULL;
	}
}
