<?php
declare(strict_types=1);
namespace NF\Modules\Classifieds\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Classifieds\Classifieds;

class Admin extends Controller_Module
{
	public function index($cats, $ads, $filters)
	{
		$this->title($this->lang('Petites annonces'))->icon('fas fa-bullhorn');

		// Réglage modération a priori (config).
		$this->form()
			 ->add_rules([
				'classifieds_moderation' => ['label' => $this->lang('Modération'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Valider les annonces avant publication')], 'checked' => ['1' => !empty($this->config->classifieds_moderation)]]
			 ])
			 ->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$this->config('classifieds_moderation', in_array('1', $post['classifieds_moderation'] ?? []) ? 1 : 0, 'int');
			notify($this->lang('Réglage enregistré.'));
			redirect('admin/classifieds');
		}

		// Catégories
		if (empty($cats))
		{
			$cats_body = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		}
		else
		{
			$cats_body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Annonces').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($cats as $c)
			{
				$slug = url_title($c['title']);
				$cats_body .= '<tr>'
					.'<td><strong>'.htmlspecialchars((string) ($c['title'])).'</strong></td>'
					.'<td class="text-end">'.(int)$c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/classifieds/cat/'.$c['id'].'/'.$slug).'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/classifieds/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ?')), ENT_QUOTES).'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$cats_body .= '</tbody></table>';
		}

		// Annonces
		if (empty($ads))
		{
			$ads_body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune annonce ne correspond.'))
				: $this->admin_empty('fas fa-bullhorn', $this->lang('Aucune annonce.'));
		}
		else
		{
			$ads_body = '<div class="nf-card-grid">';
			foreach ($ads as $a)
			{
				$slug = url_title($a['title']);
				$ads_body .= '<div class="nf-content-card">';
				$ads_body .= '<div class="nf-content-card-head">';
				$ads_body .= '<div class="nf-content-card-title"><a href="'.url('classifieds/'.$a['id'].'/'.$slug).'" target="_blank">'.htmlspecialchars((string) ($a['title'])).'</a></div>';
				$ads_body .= Classifieds::status_label($a['status']);
				$ads_body .= '</div>';
				$ads_body .= '<div class="nf-content-card-meta">';
				$ads_body .= '<span>'.Classifieds::type_label($a['ad_type']).'</span>';
				$ads_body .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars((string) ($a['cat_title'])).'</span>';
				$ads_body .= '<span>'.Classifieds::format_price($a['price'], $this).'</span>';
				$ads_body .= '</div>';
				$ads_body .= '<div class="nf-content-card-meta">';
				$author = $a['author_id'] ? $this->user->link($a['author_id'], $a['author']) : '<i>'.$this->lang('Anonyme').'</i>';
				$ads_body .= '<span><i class="fas fa-user"></i> '.$author.'</span>';
				$ads_body .= '<span><i class="fas fa-eye"></i> '.(int)$a['views'].'</span>';
				$ads_body .= '</div>';
				$ads_body .= '<div class="nf-content-card-foot">';
				$ads_body .= '<span class="nf-content-card-spacer"></span>';
				if ($a['status'] === 'pending')
				{
					$ads_body .= '<a class="btn btn-sm btn-outline-success" href="'.$this->csrf_url('admin/classifieds/'.$a['id'].'/'.$slug.'/approve').'" title="'.$this->lang('Valider').'"><i class="fas fa-check"></i></a> ';
					$ads_body .= '<a class="btn btn-sm btn-outline-warning" href="'.$this->csrf_url('admin/classifieds/'.$a['id'].'/'.$slug.'/reject').'" title="'.$this->lang('Refuser').'"><i class="fas fa-ban"></i></a> ';
				}
				$ads_body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/classifieds/delete/'.$a['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ?')), ENT_QUOTES).'"><i class="far fa-trash-alt"></i></a>';
				$ads_body .= '</div>';
				$ads_body .= '</div>';
			}
			$ads_body .= '</div>';
		}

		// Barre de recherche / filtre (GET).
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filters['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher un titre…')), ENT_QUOTES).'" style="max-width:240px;">';
		$toolbar .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$toolbar .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';
		foreach ($cats as $c)
		{
			$toolbar .= '<option value="'.(int)$c['id'].'"'.((int)$filters['category'] === (int)$c['id'] ? ' selected' : '').'>'.htmlspecialchars((string) ($c['title'])).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'pending' => $this->lang('En attente'), 'published' => $this->lang('Publiées'), 'closed' => $this->lang('Clôturées'), 'rejected' => $this->lang('Refusées')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.htmlspecialchars((string) ($label)).'</option>';
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

		$ads_body = $toolbar.$ads_body.$pagination;

		$ads_subtitle = $filters['pending'] > 0 ? '<span class="badge text-bg-warning">'.(int)$filters['pending'].' '.$this->lang('en attente').'</span>' : '';
		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/classifieds/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';

		return $this->admin_card('fas fa-cog', $this->lang('Réglages'), $this->form()->display())
			.'<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_body, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-bullhorn', $this->lang('Annonces'), $ads_body, $ads_subtitle).'</div>'
			.'</div>';
	}

	public function _cat_add()  { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }
	public function _cat_delete($c)
	{
		$this->check_csrf('admin/classifieds');

		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_classifieds')->where('category_id', $c['id'])->row();
		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d annonce(s) dans cette catégorie.', $nb), 'danger');
			redirect('admin/classifieds');
		}
		NeoFrag()->db->where('id', $c['id'])->delete('nf_classifieds_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/classifieds');
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
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$data = ['title' => $post['title'], 'sort_order' => (int)$post['sort_order']];
			if ($is_new) NeoFrag()->db->insert('nf_classifieds_categories', $data);
			else         NeoFrag()->db->where('id', $c['id'])->update('nf_classifieds_categories', $data);

			notify($is_new ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/classifieds');
		}

		return $this->admin_card($is_new ? 'fas fa-folder-plus' : 'fas fa-folder-open', $is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie : %s', $c['title']), $this->form()->display());
	}

	public function _approve($ad)
	{
		$this->check_csrf('admin/classifieds');

		NeoFrag()->db->where('id', $ad['id'])->update('nf_classifieds', ['status' => 'published']);
		$this->_notify_author($ad, $this->lang('Ton annonce « %s » a été validée.', $ad['title']));
		notify($this->lang('Annonce validée.'));
		redirect('admin/classifieds');
	}

	public function _reject($ad)
	{
		$this->check_csrf('admin/classifieds');

		NeoFrag()->db->where('id', $ad['id'])->update('nf_classifieds', ['status' => 'rejected']);
		$this->_notify_author($ad, $this->lang('Ton annonce « %s » a été refusée.', $ad['title']));
		notify($this->lang('Annonce refusée.'));
		redirect('admin/classifieds');
	}

	public function _delete($ad)
	{
		$this->check_csrf('admin/classifieds');

		NeoFrag()->db->where('id', $ad['id'])->delete('nf_classifieds');
		notify($this->lang('Annonce supprimée.'));
		redirect('admin/classifieds');
	}

	private function _notify_author($ad, $message)
	{
		if ($ad['user_id'] && ($notifications = $this->module('notifications')))
		{
			$notifications->push((int)$ad['user_id'], 'classifieds-status', $message, 'classifieds/'.$ad['id'].'/'.url_title($ad['title']));
		}
	}
}
