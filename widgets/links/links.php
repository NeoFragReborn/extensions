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
			'description' => $this->lang('Les liens les plus cliqués de l\'annuaire, avec leur nombre de clics, et un lien vers l\'annuaire complet ; le nombre de liens affichés se règle.'),
			'icon'        => 'fas fa-link',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['links'],
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['popular' => $this->lang('Liens populaires')]
		];
	}
}
