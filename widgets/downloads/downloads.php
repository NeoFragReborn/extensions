<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Downloads;

use NF\NeoFrag\Addons\Widget;

class Downloads extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Téléchargements'),
			'description' => $this->lang('Liste des derniers fichiers à télécharger.'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Téléchargements populaires')]
		];
	}
}
