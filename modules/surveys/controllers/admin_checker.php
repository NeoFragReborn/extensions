<?php
declare(strict_types=1);
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

		/*
		 * La LISTE montre la meilleure traduction (`join_lang`), jamais le vide ; l'écran d'ÉDITION
		 * ouvre la langue où l'on travaille, et elle seule (m32, 2026-10-15).
		 */
		$db = NeoFrag()->db	->select('s.*', 'sl.title', 'sl.description', 'COUNT(v.id) AS total_votes', 'COUNT(DISTINCT o.id) AS nb_options')
							->from('nf_surveys s')
							->join_lang('nf_surveys_lang sl', 'survey_id', 's.id', NULL, 'LEFT')
							->join('nf_surveys_votes v', 's.id = v.survey_id', 'LEFT')
							->join('nf_surveys_options o', 's.id = o.survey_id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('sl.title LIKE', $like, 'OR', 'sl.description LIKE', $like);
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

		$filters['sort_cols'] = [
			'date'    => $this->lang('Date'),
			'title'   => $this->lang('Titre'),
			'votes'   => $this->lang('Votes'),
			'options' => $this->lang('Options'),
		];
		list($surveys, $filters['sort']) = $this->sort_items($surveys, [
			'date'    => 'created_at',
			'title'   => 'title',
			'votes'   => 'total_votes',
			'options' => 'nb_options',
		], 'date', 'desc');

		return [$this->module->pagination->fix_items_per_page(20)->get_data($surveys, $page), $filters];
	}

	public function _add() { return [NULL]; }
	public function _edit($id, $title)
	{
		$s = NeoFrag()->db->select('*')->from('nf_surveys')->where('id', $id)->row();

		if ($s)
		{
			$langue = (string) $this->config->lang->info()->name;

			$ecrit = NeoFrag()->db	->select('title', 'description')
									->from('nf_surveys_lang')
									->where('survey_id', (int) $s['id'])
									->where('lang', $langue)
									->row();

			$lu = NeoFrag()->db	->select('title')
								->from('nf_surveys_lang')
								->where('survey_id', (int) $s['id'])
								->order_by("lang = 'fr' DESC, lang ASC")
								->row();

			// Le texte TEL QU'IL EST ÉCRIT dans la langue de l'administration (vide si rien n'y est
			// encore écrit), et le titre LU pour l'en-tête.
			$s = $s + [
				'title'       => is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '',
				'description' => is_array($ecrit) ? (string) ($ecrit['description'] ?? '') : '',
				'title_lu'    => is_string($lu) ? $lu : (is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '')
			];
		}
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
