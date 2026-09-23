<?php
declare(strict_types=1);
namespace NF\Modules\Places\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		// `LEFT` : une catégorie SANS lieu doit rester dans la liste, la jointure ne servant qu'à
		// compter. C'est le motif qui avait fait disparaître une galerie vide de son propre écran.
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'c.icon', 'c.color', 'COUNT(p.id) AS nb')
								->from('nf_places_categories c')
								->join('nf_places p', 'c.id = p.category_id', 'LEFT')
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

		$db = NeoFrag()->db	->select('p.id', 'p.title', 'p.address', 'p.latitude', 'p.longitude', 'p.published', 'p.category_id', 'c.title AS cat_title')
							->from('nf_places p')
							->join('nf_places_categories c', 'p.category_id = c.id');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('p.title LIKE', $comme, 'OR', 'p.address LIKE', $comme, 'OR', 'p.description LIKE', $comme);
		}
		if ($filtres['category'])
		{
			$db->where('p.category_id', $filtres['category']);
		}
		if ($filtres['status'] === 'published')
		{
			$db->where('p.published', 1);
		}
		else if ($filtres['status'] === 'draft')
		{
			$db->where('p.published', 0);
		}

		$lieux = $db->order_by('c.sort_order ASC, p.sort_order ASC, p.title ASC')->limit(self::SEARCH_CAP)->get();

		$publies = 0;
		foreach ($lieux as $l) { if (!empty($l['published'])) $publies++; }

		$filtres['matched']   = count($lieux);
		$filtres['published'] = $publies;
		$filtres['drafts']    = count($lieux) - $publies;
		$filtres['active']    = $filtres['q'] !== '' || $filtres['category'] || $filtres['status'] !== '';

		$filtres['sort_cols'] = [
			'title'    => $this->lang('Titre'),
			'category' => $this->lang('Catégorie'),
			'status'   => $this->lang('Statut')
		];
		list($lieux, $filtres['sort']) = $this->sort_items($lieux, [
			'title'    => 'title',
			'category' => 'cat_title',
			'status'   => 'published'
		], 'title', 'asc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($lieux, $page), $filtres];
	}

	public function _p_add() { return [NULL]; }

	public function _p_edit($id, $title)
	{
		$p = NeoFrag()->db->select('*')->from('nf_places')->where('id', $id)->row();
		return $p ? [$p] : NULL;
	}

	public function _p_delete($id, $title)
	{
		$p = NeoFrag()->db->select('id', 'title')->from('nf_places')->where('id', $id)->row();
		return $p ? [$p] : NULL;
	}

	public function _cat_add() { return [NULL]; }

	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_places_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}

	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_places_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
