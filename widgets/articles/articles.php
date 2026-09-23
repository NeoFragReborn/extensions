<?php
declare(strict_types=1);
namespace NF\Widgets\Articles;
use NF\NeoFrag\Addons\Widget;

class Articles extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Articles'),
			'description' => $this->lang('Liste des derniers articles publiés.'),
			'icon'        => 'far fa-newspaper',
			'author'  => 'NeoFrag',
			'license' => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version' => '1.0',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['index' => $this->lang('Articles récents')]
		];
	}
}
