<?php
declare(strict_types=1);
namespace NF\Modules\Recipes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		// `LEFT` : une catégorie SANS recette doit rester dans la liste, la jointure ne servant qu'à
		// compter. C'est le motif qui avait fait disparaître une galerie vide de son propre écran.
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'COUNT(r.id) AS nb')
								->from('nf_recipes_categories c')
								->join('nf_recipes r', 'c.id = r.category_id', 'LEFT')
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

		$db = NeoFrag()->db	->select('r.id', 'r.title', 'r.intro', 'r.servings', 'r.prep_minutes', 'r.cook_minutes', 'r.published', 'r.category_id', 'c.title AS cat_title')
							->from('nf_recipes r')
							->join('nf_recipes_categories c', 'r.category_id = c.id');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('r.title LIKE', $comme, 'OR', 'r.intro LIKE', $comme, 'OR', 'r.ingredients LIKE', $comme);
		}
		if ($filtres['category'])
		{
			$db->where('r.category_id', $filtres['category']);
		}
		if ($filtres['status'] === 'published')
		{
			$db->where('r.published', 1);
		}
		else if ($filtres['status'] === 'draft')
		{
			$db->where('r.published', 0);
		}

		$recettes = $db->order_by('c.sort_order ASC, r.sort_order ASC, r.id ASC')->limit(self::SEARCH_CAP)->get();

		$publiees = 0;
		foreach ($recettes as $r) { if (!empty($r['published'])) $publiees++; }

		$filtres['matched']   = count($recettes);
		$filtres['published'] = $publiees;
		$filtres['drafts']    = count($recettes) - $publiees;
		$filtres['active']    = $filtres['q'] !== '' || $filtres['category'] || $filtres['status'] !== '';

		$filtres['sort_cols'] = [
			'title'    => $this->lang('Titre'),
			'category' => $this->lang('Catégorie'),
			'status'   => $this->lang('Statut')
		];
		list($recettes, $filtres['sort']) = $this->sort_items($recettes, [
			'title'    => 'title',
			'category' => 'cat_title',
			'status'   => 'published'
		], 'title', 'asc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($recettes, $page), $filtres];
	}

	public function _r_add() { return [NULL]; }

	public function _r_edit($id, $title)
	{
		$r = NeoFrag()->db->select('*')->from('nf_recipes')->where('id', $id)->row();
		return $r ? [$r] : NULL;
	}

	public function _r_delete($id, $title)
	{
		$r = NeoFrag()->db->select('id', 'title')->from('nf_recipes')->where('id', $id)->row();
		return $r ? [$r] : NULL;
	}

	public function _cat_add() { return [NULL]; }

	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_recipes_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}

	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_recipes_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
