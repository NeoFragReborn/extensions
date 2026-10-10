<?php
declare(strict_types=1);
namespace NF\Modules\Classifieds\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		return [$this->_categories(), $this->_ads()];
	}

	public function _category($id, $title)
	{
		$cat = NeoFrag()->db->select('id', 'title')->from('nf_classifieds_categories')->where('id', $id)->row(FALSE);
		if (empty($cat))
		{
			return;
		}
		return [$cat, $this->_categories(), $this->_ads((int)$id)];
	}

	public function _new()
	{
		$this->error->unconnected();
		return [$this->_cats_list()];
	}

	public function _show($id, $title)
	{
		$ad = NeoFrag()->db	->select('a.*', 'c.title AS cat_title', 'u.username AS author', 'u.id AS author_id', 'UNIX_TIMESTAMP(a.created_at) AS created_ts')
							->from('nf_classifieds a')
							->join('nf_classifieds_categories c', 'a.category_id = c.id', 'LEFT')
							->join('nf_user u', 'a.user_id = u.id', 'LEFT')
							->where('a.id', $id)
							->row(FALSE);

		if (empty($ad) || !$this->_can_view($ad))
		{
			return;
		}

		// Compteur de vues : on ne compte pas l'auteur (anti-gonflage), cf. politique vues du projet.
		$viewer = NeoFrag()->user ? (int)NeoFrag()->user->id : 0;
		if (!$viewer || $viewer !== (int)$ad['user_id'])
		{
			// updated_at = updated_at : une visite ne modifie pas l'annonce (la colonne suit ON UPDATE).
			NeoFrag()->db->execute('UPDATE nf_classifieds SET views = views + 1, updated_at = updated_at WHERE id = '.(int)$id);
		}

		return [$ad];
	}

	public function _edit($id, $title)
	{
		$this->error->unconnected();
		$ad = NeoFrag()->db->select('*')->from('nf_classifieds')->where('id', $id)->row(FALSE);
		if (empty($ad) || (int)$ad['user_id'] !== (int)NeoFrag()->user->id)
		{
			return;
		}
		return [$ad, $this->_cats_list()];
	}

	public function _close($id, $title)
	{
		$this->error->unconnected();
		$ad = NeoFrag()->db->select('id', 'title', 'user_id')->from('nf_classifieds')->where('id', $id)->row(FALSE);
		if (empty($ad) || (int)$ad['user_id'] !== (int)NeoFrag()->user->id)
		{
			return;
		}
		return [$ad];
	}

	public function _contact($id, $title)
	{
		$this->error->unconnected();
		$ad = NeoFrag()->db->select('id', 'title', 'user_id')->from('nf_classifieds')->where('id', $id)->where('status', 'published')->row(FALSE);
		return $ad ? [$ad] : NULL;
	}

	private function _can_view($ad)
	{
		// Une annonce d'un membre sous shadow ban ne se montre qu'à lui et aux modérateurs (audit du 2026-10-09).
		if (in_array((int) $ad['user_id'], NeoFrag()->moderation->auteurs_masques(), TRUE))
		{
			return FALSE;
		}

		if (in_array($ad['status'], ['published', 'closed'], TRUE))
		{
			return TRUE;
		}
		return NeoFrag()->user && (int)NeoFrag()->user->id === (int)$ad['user_id'];
	}

	private function _categories()
	{
		return NeoFrag()->db	->select('c.id', 'c.title', 'COUNT(a.id) AS nb')
								->from('nf_classifieds_categories c')
								->join('nf_classifieds a', "c.id = a.category_id AND a.status = 'published'", 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC, c.title ASC')
								->get();
	}

	private function _cats_list()
	{
		$out = [];
		foreach (NeoFrag()->db->select('id', 'title')->from('nf_classifieds_categories')->order_by('sort_order ASC, title ASC')->get() as $c)
		{
			$out[(int)$c['id']] = $c['title'];
		}
		return $out;
	}

	private function _ads($category_id = 0)
	{
		$db = NeoFrag()->db	->select('a.id', 'a.title', 'a.ad_type', 'a.price', 'a.image', 'a.views', 'c.title AS cat_title', 'u.username AS author', 'u.id AS author_id', 'UNIX_TIMESTAMP(a.created_at) AS created_ts')
							->from('nf_classifieds a')
							->join('nf_classifieds_categories c', 'a.category_id = c.id', 'LEFT')
							->join('nf_user u', 'a.user_id = u.id', 'LEFT')
							->where('a.status', 'published');

		if ($category_id)
		{
			$db->where('a.category_id', $category_id);
		}

		if ($sans_masques = NeoFrag()->moderation->condition_sans_masques('a.user_id'))
		{
			$db->where($sans_masques);
		}

		return $db->order_by('a.created_at DESC')->limit(60)->get();
	}
}
