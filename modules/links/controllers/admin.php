<?php
namespace NF\Modules\Links\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($cats, $links, $filters)
	{
		$this->title($this->lang('Annuaire de liens'))->icon('fas fa-link');

		// Cats card
		if (empty($cats)) {
			$cats_body = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		} else {
			$cats_body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Liens').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($cats as $c) {
				$slug = url_title($c['title']);
				$cats_body .= '<tr>'
					.'<td><strong>'.htmlspecialchars($c['title']).'</strong></td>'
					.'<td class="text-end">'.(int)$c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/links/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/links/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$cats_body .= '</tbody></table>';
		}

		// Links cards
		$pub    = (int)$filters['published'];
		$drafts = (int)$filters['drafts'];

		if (empty($links)) {
			$ls_body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun lien ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-link', $this->lang('Aucun lien.'));
		} else {
			$ls_body = '<div class="nf-card-grid">';
			foreach ($links as $l) {
				$slug = url_title($l['title']);
				$published = !empty($l['published']);
				$host = parse_url($l['url'], PHP_URL_HOST) ?: $l['url'];

				$ls_body .= '<div class="nf-content-card">';
				$ls_body .= '<div class="nf-content-card-head">';
				$ls_body .= '<div class="nf-content-card-title"><a href="'.htmlspecialchars($l['url']).'" target="_blank" rel="noopener">'.htmlspecialchars($l['title']).'</a></div>';
				$ls_body .= '<span class="nf-content-card-status '.($published ? 'published' : 'draft').'">';
				$ls_body .= '<i class="fas '.($published ? 'fa-check' : 'fa-clock').'"></i> '.($published ? $this->lang('Publié') : $this->lang('Brouillon'));
				$ls_body .= '</span>';
				$ls_body .= '</div>';
				$ls_body .= '<div class="nf-content-card-meta">';
				$ls_body .= '<span><i class="fas fa-external-link-alt"></i> '.htmlspecialchars($host).'</span>';
				$ls_body .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars($l['cat_title']).'</span>';
				$ls_body .= '<span title="'.$this->lang('Clics').'"><i class="fas fa-mouse-pointer"></i> '.(int)$l['clicks'].'</span>';
				$ls_body .= '</div>';
				$ls_body .= '<div class="nf-content-card-foot">';
				$ls_body .= '<span class="nf-content-card-spacer"></span>';
				$ls_body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/links/link/'.$l['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$ls_body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/links/link/delete/'.$l['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$ls_body .= '</div>';
				$ls_body .= '</div>';
			}
			$ls_body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher un titre ou une URL…'), ENT_QUOTES).'" style="max-width:240px;">';
		$toolbar .= '<select name="category" class="form-control form-control-sm" style="width:auto;">';
		$toolbar .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';
		foreach ($cats as $c)
		{
			$toolbar .= '<option value="'.(int)$c['id'].'"'.((int)$filters['category'] === (int)$c['id'] ? ' selected' : '').'>'.htmlspecialchars($c['title']).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= '<select name="status" class="form-control form-control-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiés'), 'draft' => $this->lang('Brouillons')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.htmlspecialchars($label).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= $this->sort_select($filters['sort_cols'], $filters['sort']);
		$toolbar .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';
		if (!empty($filters['active']))
		{
			$toolbar .= '<a href="'.$form_action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
			$toolbar .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int)$filters['matched'], (int)$filters['matched']).'</span>';
		}
		$toolbar .= '</form>';

		$pagination = (string)$this->module->pagination->get_pagination();
		if ($pagination !== '')
		{
			$pagination = '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
		}

		$ls_body = $toolbar.$ls_body.$pagination;

		$ls_subtitle = $pub.' '.$this->lang('publié|publiés', $pub).($drafts > 0 ? ' · '.$drafts.' '.$this->lang('brouillon|brouillons', $drafts) : '');
		$ls_actions  = '<a class="btn btn-primary btn-sm" href="'.url('admin/links/link/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau lien').'</a>';

		// Cats card
		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/links/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_body, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-link', $this->lang('Liens'), $ls_body, $ls_subtitle, $ls_actions).'</div>'
			.'</div>';
	}

	public function _link_add()    { return $this->_link_form(NULL); }
	public function _link_edit($l) { return $this->_link_form($l); }
	public function _link_delete($l)
	{
		$this->check_csrf('admin/links');

		NeoFrag()->db->where('id', $l['id'])->delete('nf_links');
		notify($this->lang('Lien supprimé.'));
		redirect('admin/links');
	}

	protected function _link_form($l)
	{
		$is_new = $l === NULL;
		$this->title($is_new ? $this->lang('Nouveau lien') : $this->lang('Éditer : %s', $l['title']))->icon('fas fa-link')->breadcrumb();

		$cats = NeoFrag()->db->select('id', 'title')->from('nf_links_categories')->order_by('sort_order ASC')->get();
		$cats_array = [];
		foreach ($cats as $c) { $cats_array[$c['id']] = $c['title']; }

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $cats_array, 'value' => $is_new ? key($cats_array) : $l['category_id'], 'rules' => 'required'],
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $l['title'], 'rules' => 'required'],
				'url'         => ['label' => $this->lang('URL'), 'type' => 'text', 'value' => $is_new ? 'https://' : $l['url'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : $l['description']],
				'sort_order'  => ['label' => $this->lang('Ordre tri'), 'type' => 'text', 'value' => $is_new ? '0' : $l['sort_order']],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Lien publié')], 'checked' => ['1' => ($is_new || !empty($l['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = [
				'category_id' => (int)$post['category_id'],
				'title'       => $post['title'],
				'url'         => $post['url'],
				'description' => $post['description'] ?? '',
				'sort_order'  => (int)$post['sort_order'],
				'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new) NeoFrag()->db->insert('nf_links', $data);
			else         NeoFrag()->db->where('id', $l['id'])->update('nf_links', $data);

			notify($is_new ? $this->lang('Lien créé.') : $this->lang('Lien modifié.'));
			redirect('admin/links');
		}

		return $this->admin_back('admin/links', $this->lang('Liens')).$this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouveau lien') : $this->lang('Éditer : %s', $l['title']), $this->form()->display());
	}

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }
	public function _cat_delete($c)
	{
		$this->check_csrf('admin/links');

		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_links')->where('category_id', $c['id'])->row();
		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d lien(s) dans cette catégorie.', $nb));
			redirect('admin/links');
		}
		NeoFrag()->db->where('id', $c['id'])->delete('nf_links_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/links');
	}

	protected function _cat_form($c)
	{
		$is_new = $c === NULL;
		$this->title($is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'      => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $c['title'], 'rules' => 'required'],
				'sort_order' => ['label' => $this->lang('Ordre tri'), 'type' => 'text', 'value' => $is_new ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = ['title' => $post['title'], 'sort_order' => (int)$post['sort_order']];
			if ($is_new) NeoFrag()->db->insert('nf_links_categories', $data);
			else         NeoFrag()->db->where('id', $c['id'])->update('nf_links_categories', $data);

			notify($is_new ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/links');
		}

		return $this->admin_back('admin/links', $this->lang('Liens')).$this->admin_card($is_new ? 'fas fa-folder-plus' : 'fas fa-folder-open', $is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie : %s', $c['title']), $this->form()->display());
	}
}
