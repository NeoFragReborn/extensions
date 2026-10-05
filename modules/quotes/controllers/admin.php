<?php
declare(strict_types=1);
namespace NF\Modules\Quotes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Quotes\Lib\Quote;

class Admin extends Controller_Module
{
	public function index($cats, $citations, $filtres)
	{
		$this->title($this->lang('Citations'))->icon('fas fa-quote-right');

		// ── Colonne des catégories ────────────────────────────────────────────
		if (empty($cats))
		{
			$cats_corps = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		}
		else
		{
			$cats_corps = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Citations').'</th><th class="text-end"></th></tr></thead><tbody>';

			foreach ($cats as $c)
			{
				$slug = url_title($c['title']);
				$cats_corps .= '<tr>'
					.'<td><strong>'.nf_texte($c['title']).'</strong></td>'
					.'<td class="text-end">'.(int) $c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/quotes/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/quotes/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer cette catégorie ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$cats_corps .= '</tbody></table>';
		}

		// ── Colonne des citations ─────────────────────────────────────────────
		$publiees = (int) $filtres['published'];
		$brouillons = (int) $filtres['drafts'];

		if (empty($citations))
		{
			$corps = !empty($filtres['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune citation ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-quote-right', $this->lang('Aucune citation.'));
		}
		else
		{
			$corps = '<div class="nf-card-grid">';

			foreach ($citations as $c)
			{
				$slug    = url_title(mb_substr($c['quote'], 0, 50));
				$publiee = !empty($c['published']);
				$apercu  = Quote::apercu($c['quote']);

				$corps .= '<div class="nf-content-card">';
				$corps .= '<div class="nf-content-card-head">';
				$corps .= '<div class="nf-content-card-title">'.nf_texte($c['author'] !== '' ? $c['author'] : $this->lang('Auteur inconnu')).'</div>';
				$corps .= '<span class="nf-content-card-status '.($publiee ? 'published' : 'draft').'">';
				$corps .= '<i class="fas '.($publiee ? 'fa-check' : 'fa-clock').'"></i> '.($publiee ? $this->lang('Publiée') : $this->lang('Brouillon'));
				$corps .= '</span>';
				$corps .= '</div>';
				$corps .= '<div class="nf-content-card-desc">'.nf_texte($apercu).'</div>';
				$corps .= '<div class="nf-content-card-meta">';
				$corps .= '<span><i class="fas fa-folder"></i> '.nf_texte($c['cat_title']).'</span>';

				if (trim((string) $c['source']) !== '')
				{
					$corps .= '<span><i class="fas fa-book"></i> '.nf_texte($c['source']).'</span>';
				}

				$corps .= '</div>';
				$corps .= '<div class="nf-content-card-foot">';
				$corps .= '<span class="nf-content-card-spacer"></span>';
				$corps .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/quotes/q/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$corps .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/quotes/q/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer cette citation ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$corps .= '</div>';
				$corps .= '</div>';
			}

			$corps .= '</div>';
		}

		// ── Barre de recherche et de filtre (GET, préservé par la pagination) ──
		$action = url($this->module->pagination->get_url());
		$barre  = '<form method="get" action="'.$action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$barre .= '<input type="text" name="q" value="'.nf_texte($filtres['q']).'" class="form-control form-control-sm" placeholder="'.nf_texte($this->lang('Rechercher une citation, un auteur, une source…')).'" style="max-width:260px;">';
		$barre .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$barre .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';

		foreach ($cats as $c)
		{
			$barre .= '<option value="'.(int) $c['id'].'"'.((int) $filtres['category'] === (int) $c['id'] ? ' selected' : '').'>'.nf_texte($c['title']).'</option>';
		}

		$barre .= '</select>';
		$barre .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';

		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiées'), 'draft' => $this->lang('Brouillons')] as $valeur => $libelle)
		{
			$barre .= '<option value="'.$valeur.'"'.($filtres['status'] === $valeur ? ' selected' : '').'>'.nf_texte($libelle).'</option>';
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

		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/quotes/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';
		$actions      = '<a class="btn btn-primary btn-sm" href="'.url('admin/quotes/q/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle citation').'</a>';
		$sous_titre   = $publiees.' '.$this->lang('publiée|publiées', $publiees).($brouillons > 0 ? ' · '.$brouillons.' '.$this->lang('brouillon|brouillons', $brouillons) : '');

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_corps, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-quote-right', $this->lang('Citations'), $corps, $sous_titre, $actions).'</div>'
			.'</div>';
	}

	// ── Citations ─────────────────────────────────────────────────────────────

	public function _q_add()     { return $this->_q_form(NULL); }
	public function _q_edit($q)  { return $this->_q_form($q); }

	public function _q_delete($q)
	{
		$this->check_csrf('admin/quotes');

		NeoFrag()->db->where('id', $q['id'])->delete('nf_quotes');
		notify($this->lang('Citation supprimée.'));
		redirect('admin/quotes');
	}

	protected function _q_form($q)
	{
		$nouvelle = $q === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle citation') : $this->lang('Éditer la citation'))->icon('fas fa-quote-right')->breadcrumb();

		$cats = NeoFrag()->db->select('id', 'title')->from('nf_quotes_categories')->order_by('sort_order ASC')->get();
		$liste = [];
		foreach ($cats as $c) { $liste[$c['id']] = $c['title']; }

		if (!$liste)
		{
			// Sans catégorie, `category_id` n'aurait aucune valeur possible : on le dit plutôt que de
			// présenter un formulaire qui ne peut pas être validé.
			notify($this->lang('Créez d\'abord une catégorie.'));
			redirect('admin/quotes/cat/add');
		}

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $liste, 'value' => $nouvelle ? key($liste) : $q['category_id'], 'rules' => 'required'],
				// `textarea` et non `editor` : une citation est du texte. Pas d'éditeur riche, donc
				// pas de HTML à assainir, et l'affichage se contente d'échapper.
				'quote'       => ['label' => $this->lang('Citation'), 'type' => 'textarea', 'value' => $nouvelle ? '' : $q['quote'], 'rules' => 'required'],
				'author'      => ['label' => $this->lang('Auteur'), 'type' => 'text', 'value' => $nouvelle ? '' : $q['author']],
				'source'      => ['label' => $this->lang('Source'), 'type' => 'text', 'value' => $nouvelle ? '' : $q['source']],
				'source_url'  => ['label' => $this->lang('Lien vers la source'), 'type' => 'text', 'value' => $nouvelle ? '' : $q['source_url']],
				'sort_order'  => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouvelle ? '0' : $q['sort_order']],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Citation publiée')], 'checked' => ['1' => ($nouvelle || !empty($q['published']))]]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = [
				'category_id' => (int) $post['category_id'],
				'quote'       => $post['quote'],
				'author'      => $post['author'],
				'source'      => $post['source'],
				'source_url'  => Quote::lien_sur($post['source_url']),
				'sort_order'  => (int) $post['sort_order'],
				'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($nouvelle) NeoFrag()->db->insert('nf_quotes', $donnees);
			else           NeoFrag()->db->where('id', $q['id'])->update('nf_quotes', $donnees);

			notify($nouvelle ? $this->lang('Citation créée.') : $this->lang('Citation modifiée.'));
			redirect('admin/quotes');
		}

		return $this->admin_card($nouvelle ? 'fas fa-plus' : 'fas fa-edit', $nouvelle ? $this->lang('Nouvelle citation') : $this->lang('Éditer la citation'), $this->form()->display());
	}

	// ── Catégories ────────────────────────────────────────────────────────────

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }

	public function _cat_delete($c)
	{
		$this->check_csrf('admin/quotes');

		$nb = (int) NeoFrag()->db->select('COUNT(*)')->from('nf_quotes')->where('category_id', $c['id'])->row();

		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d citation(s) dans cette catégorie.', $nb));
			redirect('admin/quotes');
		}

		NeoFrag()->db->where('id', $c['id'])->delete('nf_quotes_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/quotes');
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

			if ($nouvelle) NeoFrag()->db->insert('nf_quotes_categories', $donnees);
			else           NeoFrag()->db->where('id', $c['id'])->update('nf_quotes_categories', $donnees);

			notify($nouvelle ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/quotes');
		}

		return $this->admin_card($nouvelle ? 'fas fa-folder-plus' : 'fas fa-folder-open', $nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie : %s', $c['title']), $this->form()->display());
	}
}
