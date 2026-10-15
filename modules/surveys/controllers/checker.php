<?php
declare(strict_types=1);
namespace NF\Modules\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Surveys\Surveys;

class Checker extends Module_Checker
{
	public function index()
	{
		// `show_results` : la liste ne montre le total des votes que si les résultats sont visibles.
		$surveys = NeoFrag()->db	->select('s.id', 'sl.title', 'sl.description', 's.closed_at', 's.show_results', 'COUNT(DISTINCT v.id) AS total_votes')
									->from('nf_surveys s')
									->join_lang('nf_surveys_lang sl', 'survey_id', 's.id', NULL, 'LEFT')
									->join('nf_surveys_votes v', 's.id = v.survey_id', 'LEFT')
									->where('s.published', '1')
									->group_by('s.id')
									->order_by('s.created_at DESC')
									->get();
		return [$surveys];
	}

	public function _show($survey_id, $title)
	{
		$survey = NeoFrag()->db	->select('s.*', 'sl.title', 'sl.description')
								->from('nf_surveys s')
								->join_lang('nf_surveys_lang sl', 'survey_id', 's.id', NULL, 'LEFT')
								->where('s.id', $survey_id)
								->where('s.published', '1')
								->row();
		if (empty($survey)) return;

		// Le titre de l'adresse n'est pas le bon : 301 vers la bonne (elle répondait 200 à n'importe lequel).
		nf_bon_titre((string) $title, (string) $survey['title'], 'surveys/'.(int) $survey_id);

		$options = NeoFrag()->db	->select('o.id', 'ol.label', 'o.sort_order', 'COUNT(v.id) AS votes')
									->from('nf_surveys_options o')
									->join_lang('nf_surveys_options_lang ol', 'option_id', 'o.id', NULL, 'LEFT')
									->join('nf_surveys_votes v', 'o.id = v.option_id', 'LEFT')
									->where('o.survey_id', $survey_id)
									->group_by('o.id')
									->order_by('o.sort_order ASC')
									->get();

		// Même lecture que le widget (Surveys::a_vote) : par le compte, sinon par l'empreinte de l'IP.
		return [$survey, $options, Surveys::a_vote((int) $survey_id)];
	}

	public function _vote($survey_id, $title)
	{
		$survey = NeoFrag()->db	->select('s.id', 'sl.title', 's.multiple_choice', 's.closed_at', 's.published')
								->from('nf_surveys s')
								->join_lang('nf_surveys_lang sl', 'survey_id', 's.id', NULL, 'LEFT')
								->where('s.id', $survey_id)
								->row();
		if (empty($survey) || !$survey['published']) return;
		if ($survey['closed_at'] && strtotime($survey['closed_at']) <= time()) return;

		return [$survey];
	}
}
