<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Widget Publicité — panneau de réglages (Live Editor).
 */

namespace NF\Widgets\Ads\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', array_merge([
			'placement' => 'sidebar'
		], $settings));
	}
}
