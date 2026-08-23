<?php
namespace NF\Modules\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($msgs, $filters, $counts)
	{
		$this->title($this->lang('Livre d\'or'))->icon('far fa-comment-dots');

		// Actions en masse (POST) : applique puis refresh pour recharger des données fraîches.
		if (!empty($_POST['bulk_action']) && !empty($_POST['selected']) && is_array($_POST['selected']))
		{
			$ids    = array_values(array_filter(array_map('intval', $_POST['selected'])));
			$action = (string)$_POST['bulk_action'];

			if ($ids && in_array($action, ['approve', 'reject', 'delete'], TRUE))
			{
				if ($action === 'delete')
				{
					NeoFrag()->db->where('id', $ids)->delete('nf_guestbook');
					notify($this->lang('%d message supprimé.|%d messages supprimés.', count($ids), count($ids)));
				}
				else
				{
					NeoFrag()->db->where('id', $ids)->update('nf_guestbook', ['status' => $action === 'approve' ? 'approved' : 'rejected']);
					notify($this->lang('%d message mis à jour.|%d messages mis à jour.', count($ids), count($ids)));
				}

				refresh();
			}
		}

		$pending  = (int)$counts['pending'];
		$approved = (int)$counts['approved'];
		$rejected = (int)$counts['rejected'];

		if (empty($msgs)) {
			$body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun message ne correspond à ces critères.'))
				: $this->admin_empty('far fa-comment-dots', $this->lang('Aucun message dans le livre d\'or.'));
		} else {
			$status_label = ['pending' => $this->lang('En attente'), 'approved' => $this->lang('Approuvé'), 'rejected' => $this->lang('Rejeté')];
			$status_class = ['pending' => 'draft', 'approved' => 'published', 'rejected' => 'draft'];
			$status_icon  = ['pending' => 'fa-clock', 'approved' => 'fa-check', 'rejected' => 'fa-ban'];

			$body = '<div class="nf-card-grid">';
			foreach ($msgs as $m) {
				$lbl = $status_label[$m['status']] ?? $m['status'];
				$cls = $status_class[$m['status']] ?? 'draft';
				$icn = $status_icon[$m['status']] ?? 'fa-question';
				$display_name = $m['user_id'] && $m['username']
					? '<a href="'.url('user/'.$m['user_id'].'/'.url_title($m['username'])).'">'.htmlspecialchars($m['username']).'</a>'
					: htmlspecialchars($m['name']);

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title"><input type="checkbox" name="selected[]" value="'.(int)$m['id'].'" class="nf-bulk-cb" style="margin-right:6px;vertical-align:middle;"><span class="text-muted" style="font-family:monospace;font-size:11px;">#'.(int)$m['id'].'</span> '.$display_name.'</div>';
				$body .= '<span class="nf-content-card-status '.$cls.'"><i class="fas '.$icn.'"></i> '.$lbl.'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-desc">'.htmlspecialchars($m['message']).'</div>';
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="far fa-clock"></i> '.date('Y-m-d H:i', $m['ts']).'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				if ($m['status'] !== 'approved') $body .= '<a class="btn btn-sm btn-outline-success" href="'.url('admin/guestbook/approve/'.$m['id']).'" title="'.$this->lang('Approuver').'"><i class="fas fa-check"></i></a>';
				if ($m['status'] !== 'rejected') $body .= '<a class="btn btn-sm btn-outline-warning" href="'.url('admin/guestbook/reject/'.$m['id']).'" title="'.$this->lang('Rejeter').'"><i class="fas fa-ban"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/guestbook/delete/'.$m['id']).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$status_options = ['' => $this->lang('Tous les statuts'), 'pending' => $this->lang('En attente'), 'approved' => $this->lang('Approuvé'), 'rejected' => $this->lang('Rejeté')];
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher un message ou un nom…'), ENT_QUOTES).'" style="max-width:260px;">';
		$toolbar .= '<select name="status" class="form-control form-control-sm" style="width:auto;">';
		foreach ($status_options as $val => $label)
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

		// Enveloppe la grille dans un formulaire POST avec barre d'action groupée.
		if (!empty($msgs))
		{
			$bulk_bar = '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">'
				.'<label style="display:flex;align-items:center;gap:6px;font-size:13px;margin:0;cursor:pointer;"><input type="checkbox" id="nf-bulk-all"> '.$this->lang('Tout sélectionner').'</label>'
				.'<select name="bulk_action" class="form-control form-control-sm" style="width:auto;" required>'
					.'<option value="">'.$this->lang('Action groupée…').'</option>'
					.'<option value="approve">'.$this->lang('Approuver').'</option>'
					.'<option value="reject">'.$this->lang('Rejeter').'</option>'
					.'<option value="delete">'.$this->lang('Supprimer').'</option>'
				.'</select>'
				.'<button type="submit" class="btn btn-sm btn-primary" data-confirm="'.htmlspecialchars($this->lang('Appliquer l\'action aux messages sélectionnés ?'), ENT_QUOTES).'">'.$this->lang('Appliquer').'</button>'
				.'</div>';

			$body = '<form method="post" action="'.htmlspecialchars(url($this->url->request), ENT_QUOTES).'">'.$bulk_bar.$body.'</form>'
				.'<script>(function(){var a=document.getElementById("nf-bulk-all");if(a){a.addEventListener("change",function(){document.querySelectorAll(".nf-bulk-cb").forEach(function(c){c.checked=a.checked;});});}})();</script>';
		}

		$body = $toolbar.$body.$pagination;

		$subtitle = $approved.' '.$this->lang('approuvé|approuvés', $approved);
		if ($pending > 0)  $subtitle .= ' · <span class="text-warning">'.$this->lang('%d en attente', $pending).'</span>';
		if ($rejected > 0) $subtitle .= ' · '.$rejected.' '.$this->lang('rejeté|rejetés', $rejected);

		return $this->admin_card('far fa-comment-dots', $this->lang('Livre d\'or'), $body, $subtitle);
	}

	public function _action($action, $id)
	{
		if ($action === 'delete')
		{
			NeoFrag()->db->where('id', $id)->delete('nf_guestbook');
			notify($this->lang('Message supprimé.'));
		}
		else
		{
			$status = $action === 'approve' ? 'approved' : 'rejected';
			NeoFrag()->db->where('id', $id)->update('nf_guestbook', ['status' => $status]);
			notify($action === 'approve' ? $this->lang('Message approuvé.') : $this->lang('Message rejeté.'));
		}

		redirect('admin/guestbook');
	}
}
