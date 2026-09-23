<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Links;

use NF\NeoFrag\Addons\Widget;

class Links extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Liens'),
			'description' => $this->lang('Liens populaires de l\'annuaire.'),
			'icon'        => 'fas fa-link',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Liens populaires')]
		];
	}
}
