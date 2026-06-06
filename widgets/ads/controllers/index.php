<?php
/**
 * https://neofr.ag
 * Widget Publicité — rend l'annonce de la régie pour l'emplacement configuré.
 */

namespace NF\Widgets\Ads\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$ads = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['ads']);

		if (!$ads)
		{
			return '';
		}

		return $ads->render($settings['placement'] ?? 'sidebar');
	}
}
