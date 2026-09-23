<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Seasonal\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Seasonal\Lib\Season;

/**
 * Normalise les réglages du widget avant leur enregistrement.
 *
 * Toute la décision est dans `Season`, classe pure et éprouvée par un test unitaire ; ce contrôleur
 * n'est que le point d'entrée attendu par le produit.
 */
class Checker extends Controller
{
	public function index($settings = [])
	{
		return Season::normaliser(is_array($settings) ? $settings : []);
	}
}
