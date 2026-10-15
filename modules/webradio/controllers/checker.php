<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Webradio\Lib\Schedule;

class Checker extends Module_Checker
{
	public function index()
	{
		$creneaux = NeoFrag()->db	->select('s.id', 'sl.title', 's.host', 'sl.description', 's.day', 's.start_time', 's.end_time')
									->from('nf_webradio_shows s')
									->join_lang('nf_webradio_shows_lang sl', 'show_id', 's.id', NULL, 'LEFT')
									->where('s.published', '1')
									->order_by('s.day ASC, s.start_time ASC, s.id ASC')
									->get();

		$station = [
			'name'   => (string) (NeoFrag()->config->webradio_name ?: ''),
			'stream' => Schedule::flux(NeoFrag()->config->webradio_stream),
			'site'   => Schedule::flux(NeoFrag()->config->webradio_site),
		];

		return [$station, $creneaux, Schedule::a_l_antenne($creneaux)];
	}
}
