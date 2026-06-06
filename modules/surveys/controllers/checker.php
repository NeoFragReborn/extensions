<?php
namespace NF\Modules\Surveys\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Surveys\Surveys;

class Checker extends Module_Checker
{
	public function index()
	{
		$surveys = NeoFrag()->db	->select('s.id', 's.title', 's.description', 's.closed_at', 'COUNT(DISTINCT v.id) AS total_votes')
									->from('nf_surveys s')
									->join('nf_surveys_votes v', 's.id = v.survey_id', 'LEFT')
									->where('s.published', '1')
									->group_by('s.id')
									->order_by('s.created_at DESC')
									->get();
		return [$surveys];
	}

	public function _show($survey_id, $title)
	{
		$survey = NeoFrag()->db->select('*')->from('nf_surveys')->where('id', $survey_id)->where('published', '1')->row();
		if (empty($survey)) return;

		$options = NeoFrag()->db	->select('o.id', 'o.label', 'o.sort_order', 'COUNT(v.id) AS votes')
									->from('nf_surveys_options o')
									->join('nf_surveys_votes v', 'o.id = v.option_id', 'LEFT')
									->where('o.survey_id', $survey_id)
									->group_by('o.id')
									->order_by('o.sort_order ASC')
									->get();

		$user = NeoFrag()->user();
		$ip_hash = Surveys::ip_hash();

		$user_voted = FALSE;
		if ($user)
		{
			$user_voted = !NeoFrag()->db->from('nf_surveys_votes')->where('survey_id', $survey_id)->where('user_id', $user->id)->empty();
		}
		else
		{
			$user_voted = !NeoFrag()->db->from('nf_surveys_votes')->where('survey_id', $survey_id)->where('ip_hash', $ip_hash)->empty();
		}

		return [$survey, $options, $user_voted];
	}

	public function _vote($survey_id, $title)
	{
		$survey = NeoFrag()->db->select('id', 'title', 'multiple_choice', 'closed_at', 'published')->from('nf_surveys')->where('id', $survey_id)->row();
		if (empty($survey) || !$survey['published']) return;
		if ($survey['closed_at'] && strtotime($survey['closed_at']) <= time()) return;

		return [$survey];
	}
}
