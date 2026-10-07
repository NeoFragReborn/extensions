<?php
declare(strict_types=1);
namespace NF\Modules\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Surveys\Surveys;

class Index extends Controller_Module
{
	public function index($surveys)
	{
		$this->title($this->lang('Sondages'))->icon('fas fa-poll')->breadcrumb();

		if (empty($surveys))
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucun sondage pour le moment.').'</div>';
		}
		else
		{
			// Le total des votes est un résultat : il suit la même règle que la page du sondage
			// (Surveys::resultats_visibles). Il s'affichait toujours, y compris pour « Jamais ».
			$votes        = Surveys::sondages_votes();
			$gestionnaire = ($module = $this->module('surveys')) && $module->is_authorized();

			$body = '<div class="list-group">';
			foreach ($surveys as $s)
			{
				$closed = $s['closed_at'] && strtotime($s['closed_at']) <= time();
				$status = $closed ? '<span class="badge text-bg-secondary ms-2">'.$this->lang('Fermé').'</span>' : '';
				$total = (int)$s['total_votes'];
				$visible = Surveys::resultats_visibles((string) $s['show_results'], isset($votes[(int) $s['id']]), (bool) $closed, (bool) $gestionnaire);
				$body .= '<a href="'.url('surveys/'.$s['id'].'/'.url_title($s['title'])).'" class="list-group-item list-group-item-action">';
				$body .= '<div class="d-flex justify-content-between">';
				$body .= '<strong>'.nf_texte($s['title']).$status.'</strong>';
				$body .= $visible ? '<small class="text-muted">'.$this->lang('%d vote|%d votes', $total, $total).'</small>' : '';
				$body .= '</div>';
				if (!empty($s['description']))
				{
					$body .= '<small class="text-muted">'.nf_texte($s['description']).'</small>';
				}
				$body .= '</a>';
			}
			$body .= '</div>';
		}

		return $this->panel()->title($this->lang('Sondages'), 'fas fa-poll')->body($body);
	}

	public function _show($survey, $options, $user_voted)
	{
		// Un sondage n'a pas de langue à lui : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this->title($survey['title'])->icon('fas fa-poll')->breadcrumb();

		$closed       = $survey['closed_at'] && strtotime($survey['closed_at']) <= time();
		$gestionnaire = ($module = $this->module('surveys')) && $module->is_authorized();
		$show_results = Surveys::resultats_visibles((string) $survey['show_results'], (bool) $user_voted, (bool) $closed, (bool) $gestionnaire);
		$public       = Surveys::resultats_visibles((string) $survey['show_results'], (bool) $user_voted, (bool) $closed);

		$total = 0;
		foreach ($options as $o) { $total += (int)$o['votes']; }

		$body = '';
		if (!empty($survey['description']))
		{
			$body .= '<p>'.nf_texte($survey['description']).'</p>';
		}

		if ($show_results)
		{
			$results_label = $this->lang('Résultats — %d vote(s) au total', $total);
			if ($closed) $results_label .= ' — '.$this->lang('sondage fermé');
			// Un gestionnaire voit des résultats que le réglage cache aux autres : il doit le savoir.
			if (!$public) $results_label .= ' — '.$this->lang('visibles par les gestionnaires seulement');
			$body .= '<div class="mb-2"><small class="text-muted">'.$results_label.'</small></div>';
			foreach ($options as $o)
			{
				$pct = $total > 0 ? round((int)$o['votes'] / $total * 100, 1) : 0;
				$body .= '<div class="mb-2">'
					.'<div class="d-flex justify-content-between"><span>'.nf_texte($o['label']).'</span><span><strong>'.$pct.'%</strong> ('.(int)$o['votes'].')</span></div>'
					.'<div class="progress" style="height:8px"><div class="progress-bar" role="progressbar" style="width:'.$pct.'%"></div></div>'
					.'</div>';
			}
			if (!$closed && !$user_voted)
			{
				$body .= '<a class="btn btn-primary mt-3" href="'.url('surveys/vote/'.$survey['id'].'/'.url_title($survey['title'])).'">'.$this->lang('Voter').'</a>';
			}
		}
		else if ($user_voted || $closed)
		{
			// Résultats cachés à un visiteur qui ne peut plus voter : lui dire pourquoi, plutôt que de
			// lui resservir un formulaire que le vote refusera (« Tu as déjà voté », sondage fermé).
			$body .= '<div class="alert alert-info">'
				.($user_voted ? $this->lang('Ton vote est enregistré.').' ' : '')
				.($closed ? $this->lang('Ce sondage est fermé.').' ' : '')
				.($survey['show_results'] === 'closed' && !$closed
					? $this->lang('Les résultats seront visibles à la fermeture du sondage.')
					: $this->lang('Les résultats de ce sondage ne sont pas publics.'))
				.'</div>';
		}
		else
		{
			// Form de vote
			$body .= '<form method="post" action="'.url('surveys/vote/'.$survey['id'].'/'.url_title($survey['title'])).'">';

			foreach ($options as $o)
			{
				$type = $survey['multiple_choice'] ? 'checkbox' : 'radio';
				$name = $survey['multiple_choice'] ? 'option_ids[]' : 'option_ids';
				$body .= '<div class="form-check">';
				$body .= '<input class="form-check-input" type="'.$type.'" name="'.$name.'" id="opt-'.(int)$o['id'].'" value="'.(int)$o['id'].'" required>';
				$body .= '<label class="form-check-label" for="opt-'.(int)$o['id'].'">'.nf_texte($o['label']).'</label>';
				$body .= '</div>';
			}

			$body .= '<button type="submit" class="btn btn-primary mt-3"><i class="fas fa-check"></i> '.$this->lang('Voter').'</button>';
			$body .= '</form>';
		}

		return $this->panel()->title($survey['title'], 'fas fa-poll')->body($body);
	}

	public function _vote($survey)
	{
		$user = $this->user();
		$ip_hash = Surveys::ip_hash();

		// Anti double vote
		$has_voted = $user
			? !NeoFrag()->db->from('nf_surveys_votes')->where('survey_id', $survey['id'])->where('user_id', $user->id)->empty()
			: !NeoFrag()->db->from('nf_surveys_votes')->where('survey_id', $survey['id'])->where('ip_hash', $ip_hash)->empty();

		if ($has_voted)
		{
			notify($this->lang('Tu as déjà voté à ce sondage.'));
			redirect('surveys/'.$survey['id'].'/'.url_title($survey['title']));
		}

		$option_ids = $_POST['option_ids'] ?? [];
		if (!is_array($option_ids))
		{
			$option_ids = [$option_ids];
		}
		$option_ids = array_filter(array_map('intval', $option_ids));

		if (empty($option_ids))
		{
			notify($this->lang('Aucune option sélectionnée.'));
			redirect('surveys/'.$survey['id'].'/'.url_title($survey['title']));
		}

		// Si choix unique, garder seulement le premier
		if (!$survey['multiple_choice'])
		{
			$option_ids = [reset($option_ids)];
		}

		// Vérifier que les options appartiennent bien au sondage (cast int + IN list manuel)
		$option_ids = array_filter(array_map('intval', $option_ids));
		if (empty($option_ids))
		{
			notify($this->lang('Vote invalide.'));
			redirect('surveys/'.$survey['id'].'/'.url_title($survey['title']));
		}
		$valid_ids = [];
		// Une requête à UNE colonne rend des scalaires, pas des lignes (`Db::get()`). La version
		// précédente lisait `$row['id']` sur un entier, donc 0 : aucune option n'était jamais reconnue,
		// aucun vote n'était enregistré — et le votant lisait quand même « Merci pour ton vote ! ».
		$rows = NeoFrag()->db->select('id')->from('nf_surveys_options')->where('survey_id', $survey['id'])->get();
		foreach ($rows as $option_id)
		{
			if (in_array((int)$option_id, $option_ids, TRUE))
			{
				$valid_ids[] = (int)$option_id;
			}
		}

		// Un vote dont aucune option n'appartient au sondage n'est pas un vote : le dire, au lieu de
		// remercier pour un vote qui n'a pas eu lieu.
		if (empty($valid_ids))
		{
			notify($this->lang('Vote invalide.'));
			redirect('surveys/'.$survey['id'].'/'.url_title($survey['title']));
		}

		foreach ($valid_ids as $opt_id)
		{
			NeoFrag()->db->insert('nf_surveys_votes', [
				'survey_id' => $survey['id'],
				'option_id' => $opt_id,
				'user_id'   => $user ? $user->id : NULL,
				'ip_hash'   => $ip_hash
			]);
		}

		notify($this->lang('Merci pour ton vote !'));
		redirect('surveys/'.$survey['id'].'/'.url_title($survey['title']));
	}
}
