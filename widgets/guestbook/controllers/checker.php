<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Guestbook\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function recent($settings = [])
	{
		return [
			'count'         => max(1, min(20, (int)($settings['count'] ?? 3))),
			'display_panel' => in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? $settings['display_panel'] : 'oui'
		];
	}
}
