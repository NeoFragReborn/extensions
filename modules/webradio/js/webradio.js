/**
 * Webradio.
 *
 * Le lecteur lui-même est un `<audio>` du navigateur : lire un flux est la chose même que cet
 * élément sait faire, et l'habiller de JavaScript ne rendrait pas le son meilleur. Il fonctionne
 * donc **sans ce fichier**.
 *
 * Ce script ne sert qu'à une chose : rafraîchir « ce qui passe maintenant » sans recharger la page,
 * pour qui laisse l'onglet ouvert pendant une émission. Il ne recalcule rien — il redemande au
 * serveur le fragment déjà rendu, parce que la règle du créneau qui enjambe minuit est subtile et
 * n'a pas à exister en deux exemplaires.
 */
(function () {
	'use strict';

	/** En deçà, on harcèlerait le serveur pour une information qui change toutes les heures. */
	var PERIODE_MIN = 30;

	function rafraichir(bloc, cible) {
		if (document.hidden) {
			return;   // onglet à l'arrière-plan : personne ne regarde, rien à rafraîchir
		}

		// `dataType: 'text'` : le serveur rend un fragment HTML, pas du JSON. `NF.ajax` REJETTE sur
		// un statut d'erreur, donc un échec ne remplace jamais le contenu par une page d'erreur.
		NF.ajax({ url: bloc.getAttribute('data-webradio-endpoint'), dataType: 'text' })
			.then(function (html) {
				if (typeof html === 'string' && html !== '') {
					cible.innerHTML = html;
				}
			})
			.catch(function () {
				// Le serveur ne répond pas : on garde ce qui est affiché. Une information d'il y a
				// cinq minutes vaut mieux qu'un bloc vide.
			});
	}

	function demarrer(bloc) {
		var cible = bloc.querySelector('[data-webradio-now]');

		if (!cible || !bloc.getAttribute('data-webradio-endpoint')) {
			return;
		}

		var periode = parseInt(bloc.getAttribute('data-webradio-refresh'), 10);

		if (!isFinite(periode) || periode < PERIODE_MIN) {
			periode = 60;
		}

		window.setInterval(function () { rafraichir(bloc, cible); }, periode * 1000);

		// Au retour sur l'onglet, on remet à jour tout de suite : c'est le moment où l'information
		// périmée se voit.
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				rafraichir(bloc, cible);
			}
		});
	}

	if (window.NF && typeof window.NF.ready === 'function') {
		window.NF.ready(function () {
			Array.prototype.forEach.call(document.querySelectorAll('.nf-webradio'), demarrer);
		});
	}
})();
