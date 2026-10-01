<?php
declare(strict_types=1);
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Bugtracker\Bugtracker;

class Admin extends Controller_Module
{
	public function index($tickets)
	{
		$this->title($this->lang('Bugtracker'))->icon('fas fa-bug');

		$open = $closed = $critical = 0;
		foreach ($tickets as $t)
		{
			if (in_array($t['status'], ['resolved','closed','wont_fix'], TRUE)) $closed++;
			else $open++;
			if ($t['priority'] === 'critical' && $t['status'] === 'open') $critical++;
		}

		if (empty($tickets)) {
			$body = $this->admin_empty('fas fa-bug', $this->lang('Aucun ticket pour le moment.'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($tickets as $t) {
				$slug = url_title($t['title']);
				$is_closed = in_array($t['status'], ['resolved','closed','wont_fix'], TRUE);

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title"><a href="'.url('bugtracker/'.$t['id'].'/'.$slug).'"><span class="text-muted" style="font-family:monospace;font-size:11px;">#'.(int)$t['id'].'</span> '.htmlspecialchars((string) $t['title'], ENT_QUOTES, 'UTF-8', FALSE).'</a></div>';
				$body .= '<span class="nf-content-card-status '.($is_closed ? 'draft' : 'published').'">'.Bugtracker::status_label($t['status']).'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="fas fa-tag"></i> '.Bugtracker::type_label($t['type']).'</span>';
				$body .= '<span><i class="fas fa-flag"></i> '.Bugtracker::priority_label($t['priority']).'</span>';
				$body .= '<span><i class="fas fa-user"></i> '.htmlspecialchars((string) ($t['reporter'] ?? '—'), ENT_QUOTES, 'UTF-8', FALSE).'</span>';
				$body .= '<span title="'.$this->lang('Commentaires').'"><i class="far fa-comments"></i> '.(int)$t['nb_comments'].'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/bugtracker/'.$t['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/bugtracker/delete/'.$t['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		$subtitle = $open.' '.$this->lang('ouvert|ouverts', $open);
		if ($closed > 0)   $subtitle .= ' · '.$closed.' '.$this->lang('résolu|résolus', $closed);
		if ($critical > 0) $subtitle .= ' · <span class="text-danger">'.$this->lang('%d critique|%d critiques', $critical, $critical).'</span>';
		$actions = '<a class="btn btn-primary btn-sm" href="'.url('bugtracker/new').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau ticket').'</a>';

		return $this->admin_card('fas fa-bug', $this->lang('Bugtracker'), $body, $subtitle, $actions);
	}

	public function _edit($t)
	{
		$this->title('Ticket #'.$t['id'])->icon('fas fa-edit')->breadcrumb();

		$users = NeoFrag()->db->select('id', 'username')->from('nf_user')->where('admin', '1')->where('deleted', '0')->order_by('username ASC')->get();
		$users_array = ['' => $this->lang('— Non assigné —')];
		foreach ($users as $u) { $users_array[$u['id']] = $u['username']; }

		$this->form()
			 ->add_rules([
				'status'      => ['label' => $this->lang('Statut'), 'type' => 'select', 'values' => ['open' => $this->lang('Ouvert'), 'in_progress' => $this->lang('En cours'), 'resolved' => $this->lang('Résolu'), 'closed' => $this->lang('Fermé'), 'wont_fix' => $this->lang('Wont fix'), 'duplicate' => $this->lang('Doublon')], 'value' => $t['status']],
				'duplicate_of' => ['label' => $this->lang('Doublon du ticket n°'), 'type' => 'number', 'value' => $t['duplicate_of'] ?? '', 'description' => $this->lang('Avec le statut « Doublon » : le ticket d’origine, vers lequel celui-ci renverra.')],
				'priority'    => ['label' => $this->lang('Priorité'), 'type' => 'select', 'values' => ['low' => $this->lang('Faible'), 'normal' => $this->lang('Normale'), 'high' => $this->lang('Haute'), 'critical' => $this->lang('Critique')], 'value' => $t['priority']],
				'type'        => ['label' => $this->lang('Type'), 'type' => 'select', 'values' => ['bug' => $this->lang('Bug'), 'feature' => $this->lang('Feature'), 'question' => $this->lang('Question'), 'other' => $this->lang('Autre')], 'value' => $t['type']],
				'assigned_to' => ['label' => $this->lang('Assigné à'), 'type' => 'select', 'values' => $users_array, 'value' => $t['assigned_to'] ?? ''],
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $t['title'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => $t['description'], 'rules' => 'required']
			 ])
			 ->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			// Un doublon renvoie à un AUTRE ticket qui existe ; sans lui, le statut n'a pas de sens.
			$origine = $post['status'] === 'duplicate' ? (int) ($post['duplicate_of'] ?? 0) : 0;

			if ($origine === (int) $t['id'] || ($origine && !NeoFrag()->db->select('id')->from('nf_bug_tickets')->where('id', $origine)->row()))
			{
				$origine = 0;
			}

			$this->_modele()->modifier_ticket((int) $t['id'], [
				'duplicate_of' => $origine ?: NULL,
				'title'       => $post['title'],
				'description' => $post['description'],
				'type'        => $post['type'],
				'priority'    => $post['priority'],
				'status'      => $post['status'] === 'duplicate' && !$origine ? $t['status'] : $post['status'],
				'assigned_to' => $post['assigned_to'] !== '' ? (int)$post['assigned_to'] : NULL
			]);

			// Le statut « Doublon » sans ticket d'origine valable n'est pas appliqué : on le dit, plutôt
			// qu'un « Ticket mis à jour » qui laissait croire le contraire.
			if ($post['status'] === 'duplicate' && !$origine)
			{
				notify($this->lang('Ticket mis à jour, mais pas son statut : « Doublon » demande le numéro d’un autre ticket existant.'), 'warning');
			}
			else
			{
				notify($this->lang('Ticket mis à jour.'));
			}

			redirect('admin/bugtracker');
		}

		return $this->admin_card('fas fa-bug', $this->lang('Ticket #%s — %s', $t['id'], $t['title']), $this->form()->display());
	}

	public function _delete($t)
	{
		$this->check_csrf('admin/bugtracker');

		$this->_modele()->supprimer_ticket((int) $t['id']);
		notify($this->lang('Ticket supprimé.'));
		redirect('admin/bugtracker');
	}

	/** Le modèle du module, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Bugtracker\Models\Bugtracker
	{
		$modele = $this->model('bugtracker');

		if (!$modele instanceof \NF\Modules\Bugtracker\Models\Bugtracker)
		{
			throw new \LogicException('modèle du Bugtracker introuvable');
		}

		return $modele;
	}
}
