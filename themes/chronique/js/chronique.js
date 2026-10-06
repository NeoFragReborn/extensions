/**
 * Chronique — les gestes du carnet, en JavaScript maison (aucune bibliothèque) :
 * - le « Sommaire » : un élément <details> qui s'ouvre sans script ; le script en fait un panneau plein écran qui se
 *   referme par son bouton, par Échap ou d'un clic à côté, et rend la main au bouton « Sommaire » ; dans l'éditeur
 *   en direct, il reste ouvert pour qu'on règle ce qu'il porte ;
 * - le trait de progression, sous l'en-tête : il avance avec la lecture de la page.
 * Tout mouvement suit `prefers-reduced-motion` (la feuille s'en charge).
 */
(function() {
	'use strict';

	// ── Le sommaire ─────────────────────────────────────────────────────────────────────────────────────
	function sommaire() {
		var details = document.querySelector('.ch-sommaire');
		if (!details) { return; }

		var bouton = details.querySelector('summary');
		var fermer = details.querySelector('.ch-sommaire-fermer');

		// Dans l'éditeur en direct, la zone doit rester visible et réglable.
		if (details.querySelector('.nf-le-zone')) {
			details.open = true;
			details.classList.add('is-editeur');
			return;
		}

		function refermer(rendre) {
			if (!details.open) { return; }
			details.open = false;
			if (rendre && bouton) { bouton.focus(); }
		}

		details.addEventListener('toggle', function() {
			document.documentElement.classList.toggle('ch-sommaire-ouvert', details.open);
			if (details.open) {
				var premier = details.querySelector('.ch-sommaire-zone a, .ch-sommaire-zone button');
				if (premier) { premier.focus(); }
			}
		});

		if (fermer) {
			fermer.addEventListener('click', function() { refermer(true); });
		}

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && details.open) { refermer(true); }
		});

		// Un clic sur le voile, autour du cadre, referme aussi.
		details.addEventListener('click', function(e) {
			if (details.open && e.target === details.querySelector('.ch-sommaire-cadre')) { refermer(true); }
		});

		// La touche Tab reste dans le panneau ouvert : du dernier lien au bouton « Fermer », et inversement.
		details.addEventListener('keydown', function(e) {
			if (e.key !== 'Tab' || !details.open) { return; }
			var cibles = Array.prototype.filter.call(details.querySelectorAll('.ch-sommaire-cadre a, .ch-sommaire-cadre button'), function(c) { return c.offsetParent !== null; });
			if (!cibles.length) { return; }
			var premier = cibles[0];
			var dernier = cibles[cibles.length - 1];
			if (e.shiftKey && document.activeElement === premier) { e.preventDefault(); dernier.focus(); }
			else if (!e.shiftKey && document.activeElement === dernier) { e.preventDefault(); premier.focus(); }
		});
	}

	// ── Le trait de progression ─────────────────────────────────────────────────────────────────────────
	function progression() {
		var trait = document.querySelector('.ch-progression');
		var prevu = false;
		if (!trait) { return; }

		function suivre() {
			var haut   = window.pageYOffset || document.documentElement.scrollTop;
			var course = document.documentElement.scrollHeight - window.innerHeight;
			trait.style.setProperty('--ch-lu', course > 0 ? Math.min(1, haut / course) : 0);
			document.documentElement.classList.toggle('ch-defile', haut > 8);
			prevu = false;
		}

		window.addEventListener('scroll', function() {
			if (!prevu) { window.requestAnimationFrame(suivre); prevu = true; }
		}, { passive: true });
		window.addEventListener('resize', suivre);
		suivre();
	}

	function init() {
		sommaire();
		progression();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
