<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;

use NF\Modules\Webradio\Lib\Schedule;

/**
 * Le fragment « ce qui passe maintenant », rechargé par le script sans recharger la page.
 *
 * Il étend `Index` pour réutiliser `_a_l_antenne()` : le fragment servi ici et celui rendu dans la
 * page sont produits par LA MÊME méthode. Recalculer l'émission en cours côté navigateur aurait
 * demandé de réécrire en JavaScript la règle du créneau qui enjambe minuit — deux sources de vérité
 * pour une règle subtile, c'est-à-dire deux occasions de diverger.
 */
class Ajax extends Index
{
	public function now()
	{
		$creneaux = NeoFrag()->db	->select('id', 'title', 'host', 'day', 'start_time', 'end_time')
									->from('nf_webradio_shows')
									->where('published', '1')
									->order_by('day ASC, start_time ASC, id ASC')
									->get();

		// Du HTML nu, pas du JSON : le script ne fait que remplacer le contenu d'un élément, et
		// n'a donc rien à interpréter.
		return $this->_a_l_antenne(Schedule::a_l_antenne($creneaux));
	}
}
