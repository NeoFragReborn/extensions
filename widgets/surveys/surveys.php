<?php
namespace NF\Widgets\Surveys;
use NF\NeoFrag\Addons\Widget;

class Surveys extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Sondages'),
			'description' => $this->lang('Sondage en cours sur le site.'),
			'author'  => 'NeoFrag',
			'license' => 'LGPLv3',
			'version' => '1.0',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['current' => $this->lang('Sondage en cours')]
		];
	}
}
