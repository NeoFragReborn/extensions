<?php
declare(strict_types=1);
namespace NF\Modules\Recipes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Recipes\Lib\Recipe;

class Admin extends Controller_Module
{
	public function index($cats, $recettes, $filtres)
	{
		$this->title($this->lang('Recettes'))->icon('fas fa-utensils');

		// ── Colonne des catégories ────────────────────────────────────────────
		if (empty($cats))
		{
			$cats_corps = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		}
		else
		{
			$cats_corps = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Recettes').'</th><th class="text-end"></th></tr></thead><tbody>';

			foreach ($cats as $c)
			{
				$slug = url_title($c['title']);
				$cats_corps .= '<tr>'
					.'<td><strong>'.htmlspecialchars((string) ($c['title'])).'</strong></td>'
					.'<td class="text-end">'.(int) $c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/recipes/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/recipes/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette catégorie ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$cats_corps .= '</tbody></table>';
		}

		// ── Colonne des recettes ──────────────────────────────────────────────
		$publiees   = (int) $filtres['published'];
		$brouillons = (int) $filtres['drafts'];

		if (empty($recettes))
		{
			$corps = !empty($filtres['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune recette ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-utensils', $this->lang('Aucune recette.'));
		}
		else
		{
			$corps = '<div class="nf-card-grid">';

			foreach ($recettes as $r)
			{
				$slug    = url_title($r['title']);
				$publiee = !empty($r['published']);
				$total   = (int) $r['prep_minutes'] + (int) $r['cook_minutes'];

				$corps .= '<div class="nf-content-card">';
				$corps .= '<div class="nf-content-card-head">';
				$corps .= '<div class="nf-content-card-title">'.htmlspecialchars((string) ($r['title'])).'</div>';
				$corps .= '<span class="nf-content-card-status '.($publiee ? 'published' : 'draft').'">';
				$corps .= '<i class="fas '.($publiee ? 'fa-check' : 'fa-clock').'"></i> '.($publiee ? $this->lang('Publiée') : $this->lang('Brouillon'));
				$corps .= '</span>';
				$corps .= '</div>';

				if (trim((string) $r['intro']) !== '')
				{
					$corps .= '<div class="nf-content-card-desc">'.htmlspecialchars((string) (Recipe::apercu($r['intro']))).'</div>';
				}

				$corps .= '<div class="nf-content-card-meta">';
				$corps .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars((string) ($r['cat_title'])).'</span>';

				if (($parts = (int) $r['servings']) > 0)
				{
					$corps .= '<span><i class="fas fa-user-friends"></i> '.$this->lang('%d part|%d parts', $parts, $parts).'</span>';
				}

				if ($total > 0)
				{
					$corps .= '<span><i class="far fa-clock"></i> '.htmlspecialchars((string) (Recipe::duree($total))).'</span>';
				}

				$corps .= '</div>';
				$corps .= '<div class="nf-content-card-foot">';
				$corps .= '<span class="nf-content-card-spacer"></span>';
				$corps .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/recipes/r/'.$r['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$corps .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/recipes/r/delete/'.$r['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette recette ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$corps .= '</div>';
				$corps .= '</div>';
			}

			$corps .= '</div>';
		}

		// ── Barre de recherche et de filtre (GET, préservé par la pagination) ──
		$action = url($this->module->pagination->get_url());
		$barre  = '<form method="get" action="'.$action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$barre .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filtres['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher un titre, une introduction, un ingrédient…')), ENT_QUOTES).'" style="max-width:260px;">';
		$barre .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$barre .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';

		foreach ($cats as $c)
		{
			$barre .= '<option value="'.(int) $c['id'].'"'.((int) $filtres['category'] === (int) $c['id'] ? ' selected' : '').'>'.htmlspecialchars((string) ($c['title'])).'</option>';
		}

		$barre .= '</select>';
		$barre .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';

		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiées'), 'draft' => $this->lang('Brouillons')] as $valeur => $libelle)
		{
			$barre .= '<option value="'.$valeur.'"'.($filtres['status'] === $valeur ? ' selected' : '').'>'.htmlspecialchars((string) ($libelle)).'</option>';
		}

		$barre .= '</select>';
		$barre .= $this->sort_select($filtres['sort_cols'], $filtres['sort']);
		$barre .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';

		if (!empty($filtres['active']))
		{
			$barre .= '<a href="'.$action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
			$barre .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int) $filtres['matched'], (int) $filtres['matched']).'</span>';
		}

		$barre .= '</form>';

		$pagination = (string) $this->module->pagination->get_pagination();

		if ($pagination !== '')
		{
			$pagination = '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
		}

		$corps = $barre.$corps.$pagination;

		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/recipes/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';
		$actions      = '<a class="btn btn-primary btn-sm" href="'.url('admin/recipes/r/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle recette').'</a>';
		$sous_titre   = $publiees.' '.$this->lang('publiée|publiées', $publiees).($brouillons > 0 ? ' · '.$brouillons.' '.$this->lang('brouillon|brouillons', $brouillons) : '');

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_corps, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-utensils', $this->lang('Recettes'), $corps, $sous_titre, $actions).'</div>'
			.'</div>';
	}

	// ── Recettes ──────────────────────────────────────────────────────────────

	public function _r_add()     { return $this->_r_form(NULL); }
	public function _r_edit($r)  { return $this->_r_form($r); }

	public function _r_delete($r)
	{
		$this->check_csrf('admin/recipes');

		NeoFrag()->db->where('id', $r['id'])->delete('nf_recipes');
		notify($this->lang('Recette supprimée.'));
		redirect('admin/recipes');
	}

	protected function _r_form($r)
	{
		$nouvelle = $r === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle recette') : $this->lang('Éditer la recette'))->icon('fas fa-utensils')->breadcrumb();

		$cats  = NeoFrag()->db->select('id', 'title')->from('nf_recipes_categories')->order_by('sort_order ASC')->get();
		$liste = [];
		foreach ($cats as $c) { $liste[$c['id']] = $c['title']; }

		if (!$liste)
		{
			// Sans catégorie, `category_id` n'aurait aucune valeur possible : on le dit plutôt que de
			// présenter un formulaire qui ne peut pas être validé.
			notify($this->lang('Créez d\'abord une catégorie.'));
			redirect('admin/recipes/cat/add');
		}

		$this->form()
			 ->add_rules([
				'category_id'  => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $liste, 'value' => $nouvelle ? key($liste) : $r['category_id'], 'rules' => 'required'],
				'title'        => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouvelle ? '' : $r['title'], 'rules' => 'required'],
				'intro'        => ['label' => $this->lang('Introduction'), 'type' => 'text', 'value' => $nouvelle ? '' : $r['intro']],
				// `textarea` et non `editor` : une ligne par ingrédient, une ligne par étape. Pas de
				// HTML à assainir, et la liste publiée se construit du découpage.
				'ingredients'  => ['label' => $this->lang('Ingrédients (un par ligne)'), 'type' => 'textarea', 'value' => $nouvelle ? '' : $r['ingredients'], 'rules' => 'required'],
				'steps'        => ['label' => $this->lang('Étapes (une par ligne)'), 'type' => 'textarea', 'value' => $nouvelle ? '' : $r['steps'], 'rules' => 'required'],
				'servings'     => ['label' => $this->lang('Nombre de parts'), 'type' => 'text', 'value' => $nouvelle ? '' : ($r['servings'] ?: '')],
				'prep_minutes' => ['label' => $this->lang('Préparation (minutes)'), 'type' => 'text', 'value' => $nouvelle ? '' : ($r['prep_minutes'] ?: '')],
				'cook_minutes' => ['label' => $this->lang('Cuisson (minutes)'), 'type' => 'text', 'value' => $nouvelle ? '' : ($r['cook_minutes'] ?: '')],
				'sort_order'   => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouvelle ? '0' : $r['sort_order']],
				'published'    => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Recette publiée')], 'checked' => ['1' => ($nouvelle || !empty($r['published']))]]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = [
				'category_id'  => (int) $post['category_id'],
				'title'        => $post['title'],
				'intro'        => $post['intro'],
				'ingredients'  => implode("\n", Recipe::lignes($post['ingredients'])),
				'steps'        => implode("\n", Recipe::lignes($post['steps'])),
				'servings'     => Recipe::entier($post['servings'], Recipe::PARTS_MAX),
				'prep_minutes' => Recipe::entier($post['prep_minutes'], Recipe::MINUTES_MAX),
				'cook_minutes' => Recipe::entier($post['cook_minutes'], Recipe::MINUTES_MAX),
				'sort_order'   => (int) $post['sort_order'],
				'published'    => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($nouvelle) NeoFrag()->db->insert('nf_recipes', $donnees);
			else           NeoFrag()->db->where('id', $r['id'])->update('nf_recipes', $donnees);

			notify($nouvelle ? $this->lang('Recette créée.') : $this->lang('Recette modifiée.'));
			redirect('admin/recipes');
		}

		return $this->admin_card($nouvelle ? 'fas fa-plus' : 'fas fa-edit', $nouvelle ? $this->lang('Nouvelle recette') : $this->lang('Éditer la recette'), $this->form()->display());
	}

	// ── Catégories ────────────────────────────────────────────────────────────

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }

	public function _cat_delete($c)
	{
		$this->check_csrf('admin/recipes');

		$nb = (int) NeoFrag()->db->select('COUNT(*)')->from('nf_recipes')->where('category_id', $c['id'])->row();

		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d recette(s) dans cette catégorie.', $nb));
			redirect('admin/recipes');
		}

		NeoFrag()->db->where('id', $c['id'])->delete('nf_recipes_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/recipes');
	}

	protected function _cat_form($c)
	{
		$nouvelle = $c === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'      => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouvelle ? '' : $c['title'], 'rules' => 'required'],
				'sort_order' => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouvelle ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = ['title' => $post['title'], 'sort_order' => (int) $post['sort_order']];

			if ($nouvelle) NeoFrag()->db->insert('nf_recipes_categories', $donnees);
			else           NeoFrag()->db->where('id', $c['id'])->update('nf_recipes_categories', $donnees);

			notify($nouvelle ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/recipes');
		}

		return $this->admin_card($nouvelle ? 'fas fa-folder-plus' : 'fas fa-folder-open', $nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie : %s', $c['title']), $this->form()->display());
	}
}
