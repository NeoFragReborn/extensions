<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Guestbook\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function recent($settings = [])
	{
		return $this->view('admin', array_merge([
			'count'         => 3,
			'display_panel' => 'oui'
		], $settings));
	}
}
