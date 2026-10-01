<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	/** Le lien de liaison : la page répond même s'il a expiré, pour le dire (et dire quoi faire). */
	public function _lier($jeton)
	{
		$modele = $this->model('discord');

		return [(string) $jeton, $modele instanceof \NF\Modules\Discord\Models\Discord ? $modele->liaison((string) $jeton) : NULL];
	}

	/** La confirmation : connecté, et sur un lien encore valable. */
	public function _confirmer($jeton, $reprendre)
	{
		$this->error->unconnected();

		$modele  = $this->model('discord');
		$liaison = $modele instanceof \NF\Modules\Discord\Models\Discord ? $modele->liaison((string) $jeton) : NULL;

		return $liaison ? [(string) $jeton, $liaison, (int) $reprendre === 1] : NULL;
	}
}
