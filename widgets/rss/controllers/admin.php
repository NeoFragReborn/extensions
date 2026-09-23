<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Controllers;

use NF\NeoFrag\Loadables\Controller;
use NF\Widgets\Rss\Lib\Settings;

class Admin extends Controller
{
	public function index($settings = [])
	{
		$reglages = Settings::normaliser(is_array($settings) ? $settings : []);

		return $this->view('admin', $reglages + ['durees' => Settings::DUREES]);
	}
}
