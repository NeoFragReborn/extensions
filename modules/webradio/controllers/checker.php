<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Webradio\Lib\Schedule;

class Checker extends Module_Checker
{
	public function index()
	{
		$creneaux = NeoFrag()->db	->select('id', 'title', 'host', 'description', 'day', 'start_time', 'end_time')
									->from('nf_webradio_shows')
									->where('published', '1')
									->order_by('day ASC, start_time ASC, id ASC')
									->get();

		$station = [
			'name'   => (string) (NeoFrag()->config->webradio_name ?: ''),
			'stream' => Schedule::flux(NeoFrag()->config->webradio_stream),
			'site'   => Schedule::flux(NeoFrag()->config->webradio_site),
		];

		return [$station, $creneaux, Schedule::a_l_antenne($creneaux)];
	}
}
