<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Articles\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'count'         => max(1, min(20, (int)($settings['count'] ?? 5))),
			'display_panel' => $this->_panneau($settings)
		];
	}

	public function populaires($settings = [])
	{
		return $this->index($settings);
	}

	public function une($settings = [])
	{
		return ['display_panel' => $this->_panneau($settings)];
	}

	public function categories($settings = [])
	{
		return $this->une($settings);
	}

	public function tags($settings = [])
	{
		return $this->une($settings);
	}

	public function archives($settings = [])
	{
		return $this->une($settings);
	}

	private function _panneau($settings): string
	{
		return in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? (string) ($settings['display_panel'] ?? 'oui') : 'oui';
	}
}
