<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss;

use NF\NeoFrag\Addons\Widget;

/**
 * Lecteur de flux : affiche les derniers articles d'un flux RSS ou Atom extérieur.
 *
 * À ne pas confondre avec le module `feeds`, qui fait l'inverse : il PUBLIE nos actualités et nos
 * articles en RSS 2.0 (`/feeds/news`, `/feeds/articles`). Ce widget-ci CONSOMME le flux d'un autre
 * site — celui d'un partenaire, d'un jeu, d'une ligue.
 *
 * Le point délicat n'est pas la lecture du XML, c'est le réseau : une page ne doit jamais attendre
 * un site tiers. Le rendu lit donc un cache, un contenu périmé est servi tel quel plutôt que de
 * faire patienter le visiteur, et le rafraîchissement a lieu dans le cron (`controllers/cron.php`).
 */
class Rss extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Lecteur de flux'),
			'description' => $this->lang('Les derniers articles d\'un flux RSS ou Atom extérieur, mis en cache.'),
			'icon'        => 'fas fa-rss',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Lecteur de flux')
			]
		];
	}
}
