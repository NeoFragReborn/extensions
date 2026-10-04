<?php
declare(strict_types=1);
namespace NF\Widgets\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->current($config);
	}

	public function current($config = [])
	{
		// Sans le module, ni ses tables ni sa règle d'affichage des résultats n'existent.
		if (!($module = $this->module('surveys')) || !$module->is_enabled())
		{
			return '';
		}

		// Le sondage le plus récent ouvert (non fermé)
		$survey = NeoFrag()->db	->select('id', 'title', 'show_results')
								->from('nf_surveys')
								->where('published', '1')
								->where('closed_at', NULL, 'OR', 'closed_at >', NeoFrag()->date()->sql())
								->order_by('created_at DESC')
								->row();

		if (empty($survey))
		{
			return $this->panel()
						->heading($this->lang('Sondage'), 'fas fa-poll')
						->body('<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun sondage en cours').'</small></div>');
		}

		$options = NeoFrag()->db	->select('o.id', 'o.label', 'COUNT(v.id) AS votes')
									->from('nf_surveys_options o')
									->join('nf_surveys_votes v', 'o.id = v.option_id', 'LEFT')
									->where('o.survey_id', $survey['id'])
									->group_by('o.id')
									->order_by('o.sort_order ASC')
									->get();

		$total = 0;
		foreach ($options as $o) { $total += (int)$o['votes']; }

		// La même règle que la page du sondage (Surveys::resultats_visibles) : le widget montrait les
		// pourcentages à tout le monde, y compris quand le réglage les réserve aux votants, à la
		// fermeture ou aux gestionnaires. Le sondage affiché ici est toujours ouvert.
		$gestionnaire = $module->is_authorized();
		$visibles     = \NF\Modules\Surveys\Surveys::resultats_visibles((string) $survey['show_results'], \NF\Modules\Surveys\Surveys::a_vote((int) $survey['id']), FALSE, (bool) $gestionnaire);

		$body = '<p class="mb-2"><strong>'.htmlspecialchars((string) ($survey['title'])).'</strong></p>';
		foreach ($options as $o)
		{
			if (!$visibles)
			{
				$body .= '<div class="mb-1"><small>'.htmlspecialchars((string) ($o['label'])).'</small></div>';
				continue;
			}

			$pct = $total > 0 ? round((int)$o['votes'] / $total * 100, 1) : 0;
			$body .= '<div class="mb-1"><small>'.htmlspecialchars((string) ($o['label'])).' — '.$pct.'%</small>';
			$body .= '<div class="progress" style="height:5px"><div class="progress-bar" style="width:'.$pct.'%"></div></div></div>';
		}

		return $this->panel()
					->heading($this->lang('Sondage'), 'fas fa-poll')
					->body($body)
					->footer('<a href="'.url('surveys/'.$survey['id'].'/'.url_title($survey['title'])).'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Participer / voir détails').'</a>', 'right');
	}
}
