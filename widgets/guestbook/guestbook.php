<?php
declare(strict_types=1);
namespace NF\Widgets\Guestbook;
use NF\NeoFrag\Addons\Widget;

class Guestbook extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Livre d\'or'),
			'description' => $this->lang('Derniers messages du livre d\'or.'),
			'icon'        => 'far fa-comment-dots',
			'author'  => 'NeoFrag',
			'license' => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version' => '1.0',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['recent' => $this->lang('Derniers messages')]
		];
	}
}
