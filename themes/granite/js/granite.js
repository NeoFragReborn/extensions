/**
 * Granite 2.0 « Gazette » — les gestes du journal, en JavaScript maison (aucune bibliothèque) :
 * - « En bref » : la ligne défile en boucle, sans à-coup (la piste est doublée) ; elle s'arrête au survol, au
 *   focus clavier et sur son bouton pause ; elle reste immobile si elle tient dans la largeur, dans l'éditeur en
 *   direct, ou si le visiteur a demandé moins d'animations ;
 * - les lettrines : la capitale ornée de la une et de l'article (réglage du thème) ne se pose que sur un texte d'au
 *   moins deux lignes — haute de deux, elle déborderait d'un texte d'une seule ;
 * - la barre de lecture : un trait rouge en haut de la fenêtre, qui avance avec la page ;
 * - le bouton « retour en haut », qui apparaît au défilement.
 */
(function() {
	'use strict';

	var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// ── « En bref » ─────────────────────────────────────────────────────────────────────────────────────
	function breves() {
		var fil = document.querySelector('.gz-breves-fil');
		if (!fil || fil.querySelector('.nf-le-zone')) { return; }

		var corps = fil.querySelector('.card-body');
		if (!corps || !corps.children.length) { return; }

		var elements = Array.prototype.slice.call(corps.children);
		var piste    = document.createElement('div');

		piste.className = 'gz-breves-piste';
		elements.forEach(function(el) { piste.appendChild(el); });
		corps.appendChild(piste);

		if (reduit) { return; }

		// La ligne ne boucle que si elle déborde ; mesurée de nouveau quand la fenêtre change de largeur (un
		// téléphone qu'on tourne), et une fois les polices du journal arrivées.
		function boucler() {
			if (fil.classList.contains('is-boucle') || piste.scrollWidth <= fil.clientWidth) { return; }

			// La piste doublée : quand la première moitié a fini de passer, la seconde est exactement à sa place.
			// La copie n'existe que pour l'œil : ni les lecteurs d'écran ni la touche Tab ne la voient.
			elements.forEach(function(el) {
				var copie = el.cloneNode(true);
				copie.setAttribute('aria-hidden', 'true');
				copie.querySelectorAll('a, button, input, [tabindex]').forEach(function(c) { c.setAttribute('tabindex', '-1'); });
				piste.appendChild(copie);
			});

			fil.classList.add('is-boucle');
			// Une vitesse de lecture constante (une soixantaine de pixels par seconde), quelle que soit la longueur.
			piste.style.setProperty('--gz-duree', Math.max(16, Math.round(piste.scrollWidth / 2 / 60)) + 's');

			var bouton = document.createElement('button');
			var picto  = document.createElement('i');
			bouton.type = 'button';
			bouton.className = 'gz-breves-pause';
			bouton.setAttribute('aria-pressed', 'false');
			bouton.setAttribute('aria-label', '<?php echo addslashes($this->lang('Arrêter le défilement')) ?>');
			bouton.title = bouton.getAttribute('aria-label');
			picto.className = 'fas fa-pause';
			picto.setAttribute('aria-hidden', 'true');
			bouton.appendChild(picto);
			fil.parentNode.appendChild(bouton);

			bouton.addEventListener('click', function() {
				var arrete = fil.classList.toggle('is-arrete');
				bouton.setAttribute('aria-pressed', arrete ? 'true' : 'false');
				picto.className = 'fas fa-' + (arrete ? 'play' : 'pause');
			});
		}

		boucler();
		window.addEventListener('resize', boucler);

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(boucler);
		}
	}

	// ── Les lettrines ───────────────────────────────────────────────────────────────────────────────────
	function lettrines() {
		var textes = document.querySelectorAll('.module-news > .card:first-child .card-text, .gz-journal .news-article-body');
		var prevu  = false;

		if (!textes.length) { return; }

		function mesurer() {
			textes.forEach(function(t) {
				t.classList.remove('gz-lettrine');
				var ligne = parseFloat(getComputedStyle(t).lineHeight) || 24;
				t.classList.toggle('gz-lettrine', t.getBoundingClientRect().height >= ligne * 1.9);
			});
			prevu = false;
		}

		mesurer();
		window.addEventListener('resize', function() {
			if (!prevu) { window.requestAnimationFrame(mesurer); prevu = true; }
		});

		if (document.fonts && document.fonts.ready) {
			document.fonts.ready.then(mesurer);
		}
	}

	// ── La barre de lecture et le retour en haut ────────────────────────────────────────────────────────
	function defilement() {
		var barre  = document.createElement('div');
		var bouton = document.createElement('button');
		var prevu  = false;

		barre.className = 'gz-lecture';
		barre.setAttribute('aria-hidden', 'true');
		document.body.appendChild(barre);

		bouton.type = 'button';
		bouton.className = 'gr-to-top';
		bouton.setAttribute('aria-label', '<?php echo addslashes($this->lang('Retour en haut')) ?>');
		bouton.innerHTML = '<i class="fas fa-chevron-up" aria-hidden="true"></i>';
		document.body.appendChild(bouton);

		function suivre() {
			var haut   = window.pageYOffset || document.documentElement.scrollTop;
			var course = document.documentElement.scrollHeight - window.innerHeight;

			barre.style.setProperty('--gz-lu', course > 0 ? Math.min(1, haut / course) : 0);
			bouton.classList.toggle('is-visible', haut > 400);
			prevu = false;
		}

		window.addEventListener('scroll', function() {
			if (!prevu) { window.requestAnimationFrame(suivre); prevu = true; }
		}, { passive: true });
		window.addEventListener('resize', suivre);

		bouton.addEventListener('click', function() {
			window.scrollTo({ top: 0, behavior: reduit ? 'auto' : 'smooth' });
		});

		suivre();
	}

	function init() {
		breves();
		lettrines();
		defilement();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
