<?php
declare(strict_types=1);
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
			'icon'        => 'fas fa-download',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Téléchargements populaires')]
		];
	}
}
