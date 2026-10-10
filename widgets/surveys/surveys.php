<?php
declare(strict_types=1);
namespace NF\Widgets\Surveys;
use NF\NeoFrag\Addons\Widget;

class Surveys extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Sondages'),
			'description' => $this->lang('Met en avant le sondage ouvert le plus récent : sa question, ses réponses et la part des votes de chacune, avec un lien pour participer.'),
			'icon'        => 'fas fa-poll',
			'author'  => 'NeoFrag Reborn',
			'license' => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['surveys'],
			'version' => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['current' => $this->lang('Sondage en cours')]
		];
	}
}
