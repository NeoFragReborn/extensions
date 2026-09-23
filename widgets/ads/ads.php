<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Widget Publicité — affiche une annonce de la régie pour un emplacement donné.
 * Plaçable dans n'importe quelle zone de thème via le Live Editor.
 */

namespace NF\Widgets\Ads;

use NF\NeoFrag\Addons\Widget;

class Ads extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Publicité'),
			'description' => $this->lang('Affiche une annonce de la régie pour un emplacement (masquée pour les membres sans-pub / VIP).'),
			'icon'        => 'fas fa-rectangle-ad',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0']
		];
	}
}
