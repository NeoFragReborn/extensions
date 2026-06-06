<?php
namespace NF\Widgets\Articles;
use NF\NeoFrag\Addons\Widget;

class Articles extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Articles'),
			'description' => $this->lang('Liste des derniers articles publiés.'),
			'author'  => 'NeoFrag',
			'license' => 'LGPLv3',
			'version' => '1.0',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['index' => $this->lang('Articles récents')]
		];
	}
}
