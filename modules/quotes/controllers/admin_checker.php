<?php
declare(strict_types=1);
namespace NF\Modules\Quotes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		// `LEFT` et non une jointure stricte : une catégorie SANS citation doit rester dans la liste,
		// puisque la jointure ne sert qu'à compter. C'est le motif qui avait fait disparaître une
		// galerie vide de son propre écran.
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'COUNT(q.id) AS nb')
								->from('nf_quotes_categories c')
								->join('nf_quotes q', 'c.id = q.category_id', 'LEFT')
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

		$db = NeoFrag()->db	->select('q.id', 'q.quote', 'q.author', 'q.source', 'q.published', 'q.category_id', 'c.title AS cat_title')
							->from('nf_quotes q')
							->join('nf_quotes_categories c', 'q.category_id = c.id');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('q.quote LIKE', $comme, 'OR', 'q.author LIKE', $comme, 'OR', 'q.source LIKE', $comme);
		}
		if ($filtres['category'])
		{
			$db->where('q.category_id', $filtres['category']);
		}
		if ($filtres['status'] === 'published')
		{
			$db->where('q.published', 1);
		}
		else if ($filtres['status'] === 'draft')
		{
			$db->where('q.published', 0);
		}

		$citations = $db->order_by('c.sort_order ASC, q.sort_order ASC, q.id ASC')->limit(self::SEARCH_CAP)->get();

		$publiees = 0;
		foreach ($citations as $c) { if (!empty($c['published'])) $publiees++; }

		$filtres['matched']   = count($citations);
		$filtres['published'] = $publiees;
		$filtres['drafts']    = count($citations) - $publiees;
		$filtres['active']    = $filtres['q'] !== '' || $filtres['category'] || $filtres['status'] !== '';

		$filtres['sort_cols'] = [
			'author'   => $this->lang('Auteur'),
			'category' => $this->lang('Catégorie'),
			'status'   => $this->lang('Statut')
		];
		list($citations, $filtres['sort']) = $this->sort_items($citations, [
			'author'   => 'author',
			'category' => 'cat_title',
			'status'   => 'published'
		], 'author', 'asc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($citations, $page), $filtres];
	}

	public function _q_add() { return [NULL]; }

	public function _q_edit($id, $title)
	{
		$q = NeoFrag()->db->select('*')->from('nf_quotes')->where('id', $id)->row();
		return $q ? [$q] : NULL;
	}

	public function _q_delete($id, $title)
	{
		$q = NeoFrag()->db->select('id', 'quote')->from('nf_quotes')->where('id', $id)->row();
		return $q ? [$q] : NULL;
	}

	public function _cat_add() { return [NULL]; }

	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_quotes_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}

	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_quotes_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
