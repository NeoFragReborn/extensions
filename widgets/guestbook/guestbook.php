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
			'description' => $this->lang('Les derniers messages validés du livre d\'or, avec leur auteur et leur date, et un lien vers le livre complet ; le nombre de messages affichés se règle.'),
			'icon'        => 'far fa-comment-dots',
			'author'  => 'NeoFrag Reborn',
			'license' => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['guestbook'],
			'version' => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => ['recent' => $this->lang('Derniers messages')]
		];
	}
}
