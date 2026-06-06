<?php
/**
 * https://neofr.ag
 * Widget Publicité — normalisation des réglages (emplacement).
 */

namespace NF\Widgets\Ads\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$placement = preg_replace('/[^a-z0-9_-]/i', '', (string)($settings['placement'] ?? ''));

		return [
			'placement' => $placement !== '' ? $placement : 'sidebar'
		];
	}
}
