<?php
namespace NF\Modules\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($surveys, $filters)
	{
		$this->title($this->lang('Sondages'))->icon('fas fa-poll');

		$open   = (int)$filters['open'];
		$closed = (int)$filters['closed'];
		$drafts = (int)$filters['drafts'];

		if (empty($surveys)) {
			$body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun sondage ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-poll', $this->lang('Aucun sondage pour le moment.'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($surveys as $s) {
				$slug = url_title($s['title']);
				$is_closed = $s['closed_at'] && strtotime($s['closed_at']) <= time();
				$published = !empty($s['published']);

				$status_class = !$published ? 'draft' : ($is_closed ? 'draft' : 'published');
				$status_text  = !$published ? $this->lang('Brouillon') : ($is_closed ? $this->lang('Fermé') : $this->lang('Ouvert'));
				$status_icon  = !$published ? 'fa-clock' : ($is_closed ? 'fa-lock' : 'fa-check');

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title">'.htmlspecialchars($s['title']).'</div>';
				$body .= '<span class="nf-content-card-status '.$status_class.'"><i class="fas '.$status_icon.'"></i> '.$status_text.'</span>';
				$body .= '</div>';
				if (!empty($s['description'])) {
					$body .= '<div class="nf-content-card-desc">'.htmlspecialchars(strip_tags($s['description'])).'</div>';
				}
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span title="'.$this->lang('Options').'"><i class="fas fa-list"></i> '.(int)$s['nb_options'].'</span>';
				$body .= '<span title="'.$this->lang('Votes').'"><i class="fas fa-vote-yea"></i> '.(int)$s['total_votes'].'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/surveys/'.$s['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				if (!$is_closed && $published) {
					$body .= '<a class="btn btn-sm btn-outline-warning" href="'.url('admin/surveys/close/'.$s['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Fermer ce sondage ?'), ENT_QUOTES).'" title="'.$this->lang('Fermer').'"><i class="fas fa-lock"></i></a>';
				}
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/surveys/delete/'.$s['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher un sondage…'), ENT_QUOTES).'" style="max-width:240px;">';
		$toolbar .= '<select name="status" class="form-control form-control-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'open' => $this->lang('Ouvert'), 'closed' => $this->lang('Fermé'), 'draft' => $this->lang('Brouillon')] as $val => $label)
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

		$body = $toolbar.$body.$pagination;

		$subtitle = $open.' '.$this->lang('ouvert|ouverts', $open);
		if ($closed > 0) $subtitle .= ' · '.$closed.' '.$this->lang('fermé|fermés', $closed);
		if ($drafts > 0) $subtitle .= ' · '.$drafts.' '.$this->lang('brouillon|brouillons', $drafts);

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/surveys/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau sondage').'</a>';

		return $this->admin_card('fas fa-poll', $this->lang('Sondages'), $body, $subtitle, $actions);
	}

	public function _add()    { return $this->_form(NULL); }
	public function _edit($s) { return $this->_form($s); }

	public function _delete($s)
	{
		NeoFrag()->db->where('id', $s['id'])->delete('nf_surveys');
		NeoFrag()->db->where('survey_id', $s['id'])->delete('nf_surveys_options');
		NeoFrag()->db->where('survey_id', $s['id'])->delete('nf_surveys_votes');
		notify($this->lang('Sondage supprimé.'));
		redirect('admin/surveys');
	}

	public function _close($s)
	{
		NeoFrag()->db->where('id', $s['id'])->update('nf_surveys', ['closed_at' => NeoFrag()->date()->sql()]);
		notify($this->lang('Sondage fermé.'));
		redirect('admin/surveys');
	}

	protected function _form($s)
	{
		$is_new = $s === NULL;
		$this->title($is_new ? $this->lang('Nouveau sondage') : $this->lang('Éditer sondage'))->icon('fas fa-poll')->breadcrumb();

		// Fetch existing options to allow editing them
		$existing_options = $is_new ? [] : NeoFrag()->db	->select('id', 'label', 'sort_order')
															->from('nf_surveys_options')
															->where('survey_id', $s['id'])
															->order_by('sort_order ASC')
															->get();
		$opts_str = '';
		foreach ($existing_options as $o)
		{
			$opts_str .= $o['label']."\n";
		}

		$this->form()
			 ->add_rules([
				'title'           => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $s['title'], 'rules' => 'required'],
				'description'     => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : $s['description']],
				'options'         => ['label' => $this->lang('Options (une par ligne)'), 'type' => 'textarea', 'value' => $is_new ? "Option 1\nOption 2\nOption 3" : $opts_str, 'rules' => 'required',
				                       'description' => $this->lang('Liste des choix possibles, un par ligne. Les options existantes seront remplacées par cette liste.')],
				'multiple_choice' => ['label' => $this->lang('Choix multiples'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Permettre plusieurs réponses par utilisateur')], 'checked' => ['1' => (!$is_new && !empty($s['multiple_choice']))]],
				'show_results'    => ['label' => $this->lang('Afficher les résultats'), 'type' => 'select', 'values' => [
					'always'      => $this->lang('Toujours visibles'),
					'after_vote'  => $this->lang('Après le vote (défaut)'),
					'closed'      => $this->lang('Quand le sondage est fermé'),
					'never'       => $this->lang('Jamais (pour admin uniquement)')
				], 'value' => $is_new ? 'after_vote' : $s['show_results'], 'rules' => 'required'],
				'published'       => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Sondage publié')], 'checked' => ['1' => ($is_new || !empty($s['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = [
				'title'           => $post['title'],
				'description'     => $post['description'] ?? '',
				'multiple_choice' => in_array('1', $post['multiple_choice'] ?? []) ? 1 : 0,
				'show_results'    => $post['show_results'],
				'published'       => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new)
			{
				$data['user_id'] = $this->user->id;
				NeoFrag()->db->insert('nf_surveys', $data);
				$survey_id = (int)NeoFrag()->db->driver()->insert_id();
			}
			else
			{
				$survey_id = (int)$s['id'];
				NeoFrag()->db->where('id', $survey_id)->update('nf_surveys', $data);
				NeoFrag()->db->where('survey_id', $survey_id)->delete('nf_surveys_options');
				// Note : on supprime aussi les votes pour rester cohérent (les votes pointaient sur les anciens options)
				NeoFrag()->db->where('survey_id', $survey_id)->delete('nf_surveys_votes');
			}

			$lines = preg_split('/\r?\n/', trim($post['options']));
			$order = 0;
			foreach ($lines as $line)
			{
				$line = trim($line);
				if ($line === '') continue;
				NeoFrag()->db->insert('nf_surveys_options', [
					'survey_id'  => $survey_id,
					'label'      => $line,
					'sort_order' => $order++
				]);
			}

			notify($is_new ? $this->lang('Sondage créé.') : $this->lang('Sondage modifié (votes existants effacés).'));
			redirect('admin/surveys');
		}

		return $this->admin_back('admin/surveys', $this->lang('Sondages')).$this->admin_card('fas fa-poll', $is_new ? $this->lang('Nouveau sondage') : $this->lang('Éditer le sondage'), $this->form()->display());
	}
}
