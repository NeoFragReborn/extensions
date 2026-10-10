<?php
declare(strict_types=1);
namespace NF\Widgets\Articles;
use NF\NeoFrag\Addons\Widget;

class Articles extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Blog'),
			'description' => $this->lang('Les billets du Blog : les derniers, les plus lus, celui à la une, les catégories, les tags et les archives.'),
			'icon'        => 'far fa-newspaper',
			'author'  => 'NeoFrag Reborn',
			'license' => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['articles'],
			'version' => '1.1',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends' => ['neofrag' => '0.2.0'],
			// Les widgets du Blog (2026-10-01). `index` garde son nom : les dispositions
			// existantes qui posent « Articles récents » continuent de l'afficher.
			'types'   => [
				'index'      => $this->lang('Derniers billets'),
				'populaires' => $this->lang('Les plus lus'),
				'une'        => $this->lang('À la une'),
				'categories' => $this->lang('Catégories'),
				'tags'       => $this->lang('Tags'),
				'archives'   => $this->lang('Archives'),
			]
		];
	}
}
