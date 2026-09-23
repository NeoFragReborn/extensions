<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Rss\Lib\Settings;

/**
 * Normalise les réglages du widget avant leur enregistrement.
 *
 * Toute la décision est dans `Settings`, classe pure et éprouvée par un test unitaire ; ce
 * contrôleur n'est que le point d'entrée attendu par le produit.
 */
class Checker extends Controller
{
	public function index($settings = [])
	{
		return Settings::normaliser(is_array($settings) ? $settings : []);
	}
}
