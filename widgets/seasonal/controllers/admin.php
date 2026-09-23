<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Seasonal\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Seasonal\Lib\Season;

class Admin extends Controller
{
	public function index($settings = [])
	{
		// Normalisé à l'affichage aussi : un réglage abîmé se présente alors tel qu'il sera appliqué,
		// plutôt que d'afficher une valeur que le widget n'utilisera pas.
		return $this->view('admin', Season::normaliser(is_array($settings) ? $settings : []));
	}
}
