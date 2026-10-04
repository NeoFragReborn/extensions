<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Flux — génère des flux RSS 2.0 du contenu (news, articles).
 *   /feeds          → page listant les flux
 *   /feeds/news     → RSS des actualités publiées
 *   /feeds/articles → RSS des articles publiés
 */

namespace NF\Modules\Feeds;

use NF\NeoFrag\Addons\Module;

class Feeds extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Flux RSS'),
			'description' => $this->lang('Flux RSS 2.0 des actualités et articles.'),
			'icon'        => 'fas fa-rss',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['news', 'articles'],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				''         => 'index',
				'news'     => '_news',
				'articles' => '_articles',
			]
		];
	}
}
