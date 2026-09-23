<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Seasonal\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;
use NF\Widgets\Seasonal\Lib\Season;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$reglages = Season::normaliser(is_array($settings) ? $settings : []);

		if ($reglages['effect'] === 'none' || !Season::en_cours($reglages['from'], $reglages['to']))
		{
			// Hors saison : rien. Pas de balise, pas de feuille, pas de script — un effet éteint ne
			// doit rien coûter au visiteur, ni en octets ni en calcul.
			return '';
		}

		return $this	->css('seasonal')
						->js('seasonal')
						->view('index', [
							'effet'      => $reglages['effect'],
							'particules' => Season::particules($reglages['density']),
						]);
	}
}
