<?php
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Downloads\Downloads;

class Admin extends Controller_Module
{
	public function index($cats, $files, $filters)
	{
		$this->title($this->lang('Téléchargements'))->icon('fas fa-download');

		// Categories card (table compact)
		if (empty($cats)) {
			$cats_body = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		} else {
			$cats_body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-right">'.$this->lang('Fichiers').'</th><th class="text-right"></th></tr></thead><tbody>';
			foreach ($cats as $c) {
				$slug = url_title($c['title']);
				$cats_body .= '<tr>'
					.'<td><strong>'.htmlspecialchars($c['title']).'</strong></td>'
					.'<td class="text-right">'.(int)$c['nb'].'</td>'
					.'<td class="text-right" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/downloads/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.url('admin/downloads/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$cats_body .= '</tbody></table>';
		}

		// Files cards
		$pub    = (int)$filters['published'];
		$drafts = (int)$filters['drafts'];

		if (empty($files)) {
			$fs_body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun fichier ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-file', $this->lang('Aucun fichier.'));
		} else {
			$fs_body = '<div class="nf-card-grid">';
			foreach ($files as $f) {
				$slug      = url_title($f['title']);
				$published = !empty($f['published']);

				$fs_body .= '<div class="nf-content-card">';
				$fs_body .= '<div class="nf-content-card-head">';
				$fs_body .= '<div class="nf-content-card-title">'.htmlspecialchars($f['title']).($f['version'] ? ' <small class="text-muted">v'.htmlspecialchars($f['version']).'</small>' : '').'</div>';
				$fs_body .= '<span class="nf-content-card-status '.($published ? 'published' : 'draft').'">';
				$fs_body .= '<i class="fas '.($published ? 'fa-check' : 'fa-clock').'"></i> '.($published ? $this->lang('Publié') : $this->lang('Brouillon'));
				$fs_body .= '</span>';
				$fs_body .= '</div>';
				$fs_body .= '<div class="nf-content-card-meta">';
				$fs_body .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars($f['cat_title']).'</span>';
				$fs_body .= '<span><i class="fas fa-weight"></i> '.Downloads::format_size($f['file_size_bytes']).'</span>';
				if (!empty($f['file_type']))   $fs_body .= '<span><i class="far fa-file"></i> '.htmlspecialchars($f['file_type']).'</span>';
				$fs_body .= '<span title="'.$this->lang('Téléchargements').'"><i class="fas fa-download"></i> '.(int)$f['downloads_count'].'</span>';
				$fs_body .= '</div>';
				$fs_body .= '<div class="nf-content-card-foot">';
				$fs_body .= '<span class="nf-content-card-spacer"></span>';
				$fs_body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/downloads/file/'.$f['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$fs_body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/downloads/file/delete/'.$f['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$fs_body .= '</div>';
				$fs_body .= '</div>';
			}
			$fs_body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher un titre…'), ENT_QUOTES).'" style="max-width:220px;">';
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

		$fs_body = $toolbar.$fs_body.$pagination;

		$fs_subtitle = $pub.' '.$this->lang('publié|publiés', $pub).($drafts > 0 ? ' · '.$drafts.' '.$this->lang('brouillon|brouillons', $drafts) : '');
		$fs_actions  = '<a class="btn btn-primary btn-sm" href="'.url('admin/downloads/file/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau fichier').'</a>';
		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/downloads/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_body, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-file', $this->lang('Fichiers'), $fs_body, $fs_subtitle, $fs_actions).'</div>'
			.'</div>';
	}

	public function _file_add()    { return $this->_file_form(NULL); }
	public function _file_edit($f) { return $this->_file_form($f); }
	public function _file_delete($f)
	{
		NeoFrag()->db->where('id', $f['id'])->delete('nf_downloads');
		notify($this->lang('Fichier supprimé.'));
		redirect('admin/downloads');
	}

	protected function _file_form($f)
	{
		$is_new = $f === NULL;
		$this->title($is_new ? $this->lang('Nouveau fichier') : $this->lang('Éditer : %s', $f['title']))->icon('fas fa-download')->breadcrumb();

		$cats = NeoFrag()->db->select('id', 'title')->from('nf_downloads_categories')->order_by('sort_order ASC')->get();
		$cats_array = [];
		foreach ($cats as $c) { $cats_array[$c['id']] = $c['title']; }

		$this->form()
			 ->add_rules([
				'category_id'     => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $cats_array, 'value' => $is_new ? key($cats_array) : $f['category_id'], 'rules' => 'required'],
				'title'           => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $f['title'], 'rules' => 'required'],
				'description'     => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : $f['description']],
				'file_url'        => ['label' => $this->lang('URL du fichier'), 'type' => 'text', 'value' => $is_new ? '' : $f['file_url'], 'rules' => 'required',
				                       'description' => $this->lang('URL absolue. Ex: <code>https://exemple.com/file.zip</code> ou <code>/upload/file.zip</code>')],
				'file_size_bytes' => ['label' => $this->lang('Taille en octets'), 'type' => 'text', 'value' => $is_new ? '' : ($f['file_size_bytes'] ?? '')],
				'file_type'       => ['label' => $this->lang('Type / extension'), 'type' => 'text', 'value' => $is_new ? '' : ($f['file_type'] ?? ''),
				                       'description' => $this->lang('Ex: ZIP, PDF, MP4')],
				'version'         => ['label' => $this->lang('Version'), 'type' => 'text', 'value' => $is_new ? '' : ($f['version'] ?? '')],
				'published'       => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Fichier publié')], 'checked' => ['1' => ($is_new || !empty($f['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = [
				'category_id'     => (int)$post['category_id'],
				'title'           => $post['title'],
				'description'     => $post['description'] ?? '',
				'file_url'        => $post['file_url'],
				'file_size_bytes' => $post['file_size_bytes'] !== '' ? (int)$post['file_size_bytes'] : NULL,
				'file_type'       => $post['file_type'] ?? '',
				'version'         => $post['version'] ?? '',
				'published'       => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new) NeoFrag()->db->insert('nf_downloads', $data);
			else         NeoFrag()->db->where('id', $f['id'])->update('nf_downloads', $data);

			notify($is_new ? $this->lang('Fichier créé.') : $this->lang('Fichier modifié.'));
			redirect('admin/downloads');
		}

		return $this->admin_back('admin/downloads', $this->lang('Téléchargements')).$this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouveau fichier') : $this->lang('Éditer : %s', $f['title']), $this->form()->display());
	}

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }
	public function _cat_delete($c)
	{
		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_downloads')->where('category_id', $c['id'])->row();
		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d fichier(s) dans cette catégorie.', $nb));
			redirect('admin/downloads');
		}
		NeoFrag()->db->where('id', $c['id'])->delete('nf_downloads_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/downloads');
	}

	protected function _cat_form($c)
	{
		$is_new = $c === NULL;
		$this->title($is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $c['title'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : ($c['description'] ?? '')],
				'sort_order'  => ['label' => $this->lang('Ordre tri'), 'type' => 'text', 'value' => $is_new ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = ['title' => $post['title'], 'description' => $post['description'] ?? '', 'sort_order' => (int)$post['sort_order']];
			if ($is_new) NeoFrag()->db->insert('nf_downloads_categories', $data);
			else         NeoFrag()->db->where('id', $c['id'])->update('nf_downloads_categories', $data);

			notify($is_new ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/downloads');
		}

		return $this->admin_back('admin/downloads', $this->lang('Téléchargements')).$this->admin_card($is_new ? 'fas fa-folder-plus' : 'fas fa-folder-open', $is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie : %s', $c['title']), $this->form()->display());
	}
}
