<?php
declare(strict_types=1);
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
				$body .= '<div class="nf-content-card-title">'.nf_texte($s['title']).'</div>';
				$body .= '<span class="nf-content-card-status '.$status_class.'"><i class="fas '.$status_icon.'"></i> '.$status_text.'</span>';
				$body .= '</div>';
				if (!empty($s['description'])) {
					$body .= '<div class="nf-content-card-desc">'.nf_texte(strip_tags($s['description'])).'</div>';
				}
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span title="'.$this->lang('Options').'"><i class="fas fa-list"></i> '.(int)$s['nb_options'].'</span>';
				$body .= '<span title="'.$this->lang('Votes').'"><i class="fas fa-vote-yea"></i> '.(int)$s['total_votes'].'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/surveys/'.$s['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				if (!$is_closed && $published) {
					$body .= '<a class="btn btn-sm btn-outline-warning" href="'.$this->csrf_url('admin/surveys/close/'.$s['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Fermer ce sondage ?')).'" title="'.$this->lang('Fermer').'"><i class="fas fa-lock"></i></a>';
				}
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/surveys/delete/'.$s['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.nf_texte($filters['q']).'" class="form-control form-control-sm" placeholder="'.nf_texte($this->lang('Rechercher un sondage…')).'" style="max-width:240px;">';
		$toolbar .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'open' => $this->lang('Ouvert'), 'closed' => $this->lang('Fermé'), 'draft' => $this->lang('Brouillon')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.nf_texte($label).'</option>';
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
		$this->check_csrf('admin/surveys');

		NeoFrag()->db->where('id', $s['id'])->delete('nf_surveys');
		NeoFrag()->db->where('survey_id', $s['id'])->delete('nf_surveys_options');
		NeoFrag()->db->where('survey_id', $s['id'])->delete('nf_surveys_votes');
		notify($this->lang('Sondage supprimé.'));
		redirect('admin/surveys');
	}

	public function _close($s)
	{
		$this->check_csrf('admin/surveys');

		NeoFrag()->db->where('id', $s['id'])->update('nf_surveys', ['closed_at' => NeoFrag()->date()->sql()]);
		notify($this->lang('Sondage fermé.'));
		redirect('admin/surveys');
	}

	protected function _form($s)
	{
		$is_new = $s === NULL;
		$this->title($is_new ? $this->lang('Nouveau sondage') : $this->lang('Éditer : %s', $s['title_lu'] ?? $s['title']))->icon('fas fa-poll')->breadcrumb();

		// Fetch existing options to allow editing them
		// Les réponses s'écrivent elles aussi dans la langue où l'on travaille : on les lit SANS repli,
		// sinon on proposerait le français à corriger dans l'écran allemand (m32, 2026-10-15).
		$langue = (string) $this->config->lang->info()->name;

		$existing_options = $is_new ? [] : NeoFrag()->db	->select('o.id', 'ol.label', 'o.sort_order')
															->from('nf_surveys_options o')
															->join('nf_surveys_options_lang ol', 'ol.option_id = o.id AND ol.lang = "'.NeoFrag()->db->escape_string($langue).'"', 'LEFT')
															->where('o.survey_id', $s['id'])
															->order_by('o.sort_order ASC')
															->get();
		$opts_str = '';
		foreach ($existing_options as $o)
		{
			$opts_str .= (string) $o['label']."\n";
		}

		$this->form()
			 ->add_rules([
				// Le texte d'un sondage s'écrit dans la langue où l'on travaille (m32, 2026-10-15) : on le dit.
				'title'           => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $s['title'], 'rules' => 'required',
				                       'description' => $this->lang('Écrit pour %s. Change la langue de l\'administration pour traduire ce sondage ; les autres langues gardent leur texte.', $this->config->lang->info()->title)],
				'description'     => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : $s['description']],
				'options'         => ['label' => $this->lang('Options (une par ligne)'), 'type' => 'textarea', 'value' => $is_new ? "Option 1\nOption 2\nOption 3" : $opts_str, 'rules' => 'required',
				                       'description' => $this->lang('Un choix par ligne. Une réponse garde ses votes tant qu\'elle reste dans la liste, même corrigée, déplacée ou traduite ; seule une réponse RETIRÉE perd les siens.')],
				'multiple_choice' => ['label' => $this->lang('Choix multiples'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Permettre plusieurs réponses par utilisateur')], 'checked' => ['1' => (!$is_new && !empty($s['multiple_choice']))]],
				'show_results'    => ['label' => $this->lang('Afficher les résultats'), 'type' => 'select', 'values' => [
					'always'      => $this->lang('Toujours visibles'),
					'after_vote'  => $this->lang('Après le vote (défaut)'),
					'closed'      => $this->lang('Quand le sondage est fermé'),
					'never'       => $this->lang('Jamais (pour admin uniquement)')
				], 'value' => $is_new ? 'after_vote' : $s['show_results'], 'rules' => 'required'],
				'published'       => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Sondage publié')], 'checked' => ['1' => ($is_new || !empty($s['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$data = [
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
			}

			// `ecrire_lang` CRÉE la ligne quand elle manque : un `update` sur une traduction qui
			// n'existe pas encore n'écrirait rien, et sans un mot (leçon de m30).
			NeoFrag()->db->ecrire_lang('nf_surveys_lang', ['survey_id' => $survey_id, 'lang' => $langue], [
				'title'       => $post['title'],
				'description' => $post['description'] ?? ''
			]);

			$lignes = [];

			foreach (preg_split('/\r?\n/', trim((string) $post['options'])) ?: [] as $ligne)
			{
				if (($ligne = trim($ligne)) !== '')
				{
					$lignes[] = $ligne;
				}
			}

			$retirees = $this->_poser_reponses($survey_id, $existing_options, $lignes, $langue);

			notify($is_new
				? $this->lang('Sondage créé.')
				: ($retirees
					? $this->lang('Sondage modifié. %d réponse retirée, et ses votes avec.|Sondage modifié. %d réponses retirées, et leurs votes avec.', $retirees, $retirees)
					: $this->lang('Sondage modifié. Les votes sont conservés.')));
			redirect('admin/surveys');
		}

		return $this->admin_card('fas fa-poll', $is_new ? $this->lang('Nouveau sondage') : $this->lang('Éditer le sondage'), $this->form()->display());
	}

	/**
	 * Pose la liste des réponses saisies, en gardant les VOTES de celles qui restent.
	 *
	 * Avant le 2026-10-15, enregistrer un sondage effaçait toutes ses réponses pour les recréer, et
	 * ses votes avec : corriger une faute dans la question coûtait le sondage entier. C'était aussi
	 * ce qui empêchait de le traduire — écrire la version italienne aurait détruit la française.
	 *
	 * On apparie donc, dans CETTE langue :
	 *   1. par le LIBELLÉ exact d'abord — une réponse déplacée dans la liste garde ses votes ;
	 *   2. puis, pour ce qui reste, DANS L'ORDRE — une faute corrigée garde les siens, et une langue
	 *      encore vide (tous ses libellés absents) retrouve ses réponses une à une ;
	 *   3. ce qui reste sans emploi à la fin est retiré, avec ses votes. C'est la seule perte, et
	 *      l'écran l'annonce en la comptant.
	 *
	 * @param list<array{id: int|string, label: string|null, sort_order: int|string}> $existantes
	 * @param list<string> $lignes
	 * @return int Le nombre de réponses retirées.
	 */
	private function _poser_reponses(int $survey_id, array $existantes, array $lignes, string $langue): int
	{
		$libres = [];

		foreach ($existantes as $o)
		{
			$libres[(int) $o['id']] = (string) ($o['label'] ?? '');
		}

		$paires = [];   // rang de la ligne => identifiant de la réponse
		$restes = $lignes;

		// 1. Le libellé exact.
		foreach ($restes as $rang => $ligne)
		{
			foreach ($libres as $id => $libelle)
			{
				if ($libelle !== '' && $libelle === $ligne)
				{
					$paires[$rang] = $id;
					unset($libres[$id], $restes[$rang]);
					break;
				}
			}
		}

		// 2. Ce qui reste, dans l'ordre.
		foreach (array_keys($restes) as $rang)
		{
			if (!$libres)
			{
				break;
			}

			$id = (int) array_key_first($libres);
			$paires[$rang] = $id;
			unset($libres[$id], $restes[$rang]);
		}

		// 3. Les réponses sans emploi s'en vont, avec leurs votes (aucune clé étrangère ne le fait).
		foreach (array_keys($libres) as $id)
		{
			NeoFrag()->db->where('option_id', $id)->delete('nf_surveys_votes');
			NeoFrag()->db->where('id', $id)->delete('nf_surveys_options');
		}

		foreach ($lignes as $rang => $ligne)
		{
			if (isset($paires[$rang]))
			{
				$id = (int) $paires[$rang];
				NeoFrag()->db->where('id', $id)->update('nf_surveys_options', ['sort_order' => $rang]);
			}
			else
			{
				NeoFrag()->db->insert('nf_surveys_options', ['survey_id' => $survey_id, 'sort_order' => $rang]);
				$id = (int) NeoFrag()->db->driver()->insert_id();
			}

			NeoFrag()->db->ecrire_lang('nf_surveys_options_lang', ['option_id' => $id, 'lang' => $langue], ['label' => $ligne]);
		}

		return count($libres);
	}
}
