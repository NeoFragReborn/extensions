<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Seasonal;

use NF\NeoFrag\Addons\Widget;

/**
 * Effet saisonnier : neige, confettis ou feuilles, sur une plage de dates.
 *
 * C'est un widget, et non un réglage global, pour une raison simple : le produit n'a pas de point
 * d'injection global dans le rendu, et en créer un pour un flocon serait disproportionné. Placé une
 * fois dans le pied de page d'un thème, le widget couvre l'écran entier ; retiré, il ne laisse rien.
 *
 * La plage de dates est évaluée **côté serveur** : hors saison, le widget ne rend rien du tout — ni
 * balise, ni feuille, ni script. Un effet désactivé ne doit rien coûter au visiteur.
 */
class Seasonal extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Effet saisonnier'),
			'description' => $this->lang('Neige, confettis ou feuilles sur tout l\'écran, pendant une plage de dates choisie.'),
			'icon'        => 'far fa-snowflake',
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
				'index' => $this->lang('Effet saisonnier')
			]
		];
	}
}
