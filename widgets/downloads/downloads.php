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
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Téléchargements populaires')]
		];
	}
}
