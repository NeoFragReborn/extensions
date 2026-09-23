<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Bac à sable — un endroit pour essayer la mise en forme sans rien déranger.
 *
 * Sixième et dernier des ajouts de un chantier interne. « Espace d'essai pour les membres » : le mainteneur a
 * tranché pour l'essai de MISE EN FORME, plutôt qu'une catégorie de forum jetable ou un bloc-notes.
 *
 * Ce qu'il montre, et pourquoi c'est ce qu'il faut montrer
 * --------------------------------------------------------
 * Le rendu passe par `bbcode()`, LA MÊME fonction que le forum, les événements et les awards. Un
 * bac à sable qui rendrait autrement mentirait, et c'est précisément ce qu'on vient y vérifier.
 *
 * À noter : malgré son nom, `bbcode()` ne traite plus aucun BBCode — la conversion a été retirée
 * quand le fork est passé à un éditeur produisant du HTML. Ce que la fonction fait réellement, et
 * que rien n'expliquait nulle part, c'est : auto-lier les adresses et les `@mentions`, assainir le
 * HTML contre l'allow-list, puis remplacer les `:emoji:` personnalisés.
 *
 * D'où le vrai apport du module : il affiche **ce que l'assainissement a retiré**. Un membre dont
 * le tableau disparaît ou dont l'attribut saute n'avait, jusqu'ici, aucun moyen de savoir pourquoi.
 */

namespace NF\Modules\Sandbox;

use NF\NeoFrag\Addons\Module;

class Sandbox extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Bac à sable'),
			'description' => $this->lang('Un endroit pour essayer la mise en forme et voir ce que le site en fait, sans rien publier.'),
			'icon'        => 'fas fa-flask',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'' => 'index'
			]
		];
	}
}
