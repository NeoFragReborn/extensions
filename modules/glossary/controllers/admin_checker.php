<?php
declare(strict_types=1);
namespace NF\Modules\Glossary\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		// `LEFT` : une catégorie SANS terme doit rester dans la liste, la jointure ne servant qu'à
		// compter. C'est le motif qui avait fait disparaître une galerie vide de son propre écran.
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'COUNT(t.id) AS nb')
								->from('nf_glossary_categories c')
								->join('nf_glossary_terms t', 'c.id = t.category_id', 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC, c.id ASC')
								->get();

		$cat_ids = [];
		foreach ($cats as $c) { $cat_ids[(int) $c['id']] = TRUE; }

		$filtres = [
			'q'        => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($cat_ids[(int) $_GET['category']]) ? (int) $_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('t.id', 't.term', 't.initial', 't.definition', 't.synonyms', 't.published', 't.category_id', 'c.title AS cat_title')
							->from('nf_glossary_terms t')
							->join('nf_glossary_categories c', 't.category_id = c.id');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('t.term LIKE', $comme, 'OR', 't.definition LIKE', $comme, 'OR', 't.synonyms LIKE', $comme);
		}
		if ($filtres['category'])
		{
			$db->where('t.category_id', $filtres['category']);
		}
		if ($filtres['status'] === 'published')
		{
			$db->where('t.published', 1);
		}
		else if ($filtres['status'] === 'draft')
		{
			$db->where('t.published', 0);
		}

		$termes = $db->order_by('t.initial ASC, t.term ASC')->limit(self::SEARCH_CAP)->get();

		$publies = 0;
		foreach ($termes as $t) { if (!empty($t['published'])) $publies++; }

		$filtres['matched']   = count($termes);
		$filtres['published'] = $publies;
		$filtres['drafts']    = count($termes) - $publies;
		$filtres['active']    = $filtres['q'] !== '' || $filtres['category'] || $filtres['status'] !== '';

		$filtres['sort_cols'] = [
			'term'     => $this->lang('Terme'),
			'category' => $this->lang('Catégorie'),
			'status'   => $this->lang('Statut')
		];
		list($termes, $filtres['sort']) = $this->sort_items($termes, [
			'term'     => 'term',
			'category' => 'cat_title',
			'status'   => 'published'
		], 'term', 'asc');

		return [$cats, $this->module->pagination->fix_items_per_page(30)->get_data($termes, $page), $filtres];
	}

	public function _t_add() { return [NULL]; }

	public function _t_edit($id, $title)
	{
		$t = NeoFrag()->db->select('*')->from('nf_glossary_terms')->where('id', $id)->row();
		return $t ? [$t] : NULL;
	}

	public function _t_delete($id, $title)
	{
		$t = NeoFrag()->db->select('id', 'term')->from('nf_glossary_terms')->where('id', $id)->row();
		return $t ? [$t] : NULL;
	}

	public function _cat_add() { return [NULL]; }

	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_glossary_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}

	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_glossary_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
