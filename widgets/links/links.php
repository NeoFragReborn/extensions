<?php
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
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Liens populaires')]
		];
	}
}
