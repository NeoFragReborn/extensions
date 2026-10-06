/**
 * Pulse — les gestes de la maison commune, en JavaScript maison (aucune bibliothèque) :
 * - le menu du téléphone : le bouton « Menu » ouvre les rubriques sous la barre ; Échap, un clic au dehors ou un
 *   lien suivi les referment, et la main revient au bouton ; au-delà de 992 px, les rubriques sont dans la barre ;
 * - le site en chiffres : chaque nombre défile jusqu'à sa valeur quand sa dalle paraît (une fois) ;
 * - la barre prend une ombre dès qu'on descend dans la page.
 * Rien ne bouge si le visiteur a demandé moins d'animations : les nombres sont écrits d'emblée (la feuille arrête le
 * reste).
 */
(function() {
	'use strict';

	var calme = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// ── Le menu du téléphone ────────────────────────────────────────────────────────────────────────────
	function menu() {
		var barre  = document.querySelector('.pl-barre');
		var bouton = barre && barre.querySelector('.pl-menu');
		var nav    = barre && barre.querySelector('.pl-nav');
		if (!bouton || !nav) { return; }

		function ouvrir(oui, rendre) {
			barre.classList.toggle('is-menu-ouvert', oui);
			bouton.setAttribute('aria-expanded', oui ? 'true' : 'false');
			if (oui) {
				// Le premier lien VISIBLE : la navigation porte aussi son propre bouton de repli, caché ici.
				var premier = Array.prototype.find.call(nav.querySelectorAll('a[href], button'), function(c) { return c.offsetParent !== null; });
				if (premier) { premier.focus(); }
			} else if (rendre) {
				bouton.focus();
			}
		}

		bouton.addEventListener('click', function() {
			ouvrir(!barre.classList.contains('is-menu-ouvert'), false);
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && barre.classList.contains('is-menu-ouvert')) { ouvrir(false, true); }
		});

		document.addEventListener('click', function(e) {
			if (barre.classList.contains('is-menu-ouvert') && !barre.contains(e.target)) { ouvrir(false, false); }
		});

		nav.addEventListener('click', function(e) {
			if (e.target.closest('a[href]') && !e.target.closest('[data-bs-toggle]')) { ouvrir(false, false); }
		});

		// Revenu sur grand écran, le menu n'a plus lieu d'être ouvert.
		window.addEventListener('resize', function() {
			if (window.innerWidth >= 992 && barre.classList.contains('is-menu-ouvert')) { ouvrir(false, false); }
		});
	}

	// ── Le site en chiffres ─────────────────────────────────────────────────────────────────────────────
	function chiffres() {
		var nombres = document.querySelectorAll('.nf-chiffre-valeur[data-valeur]');
		if (!nombres.length || calme || !window.IntersectionObserver) { return; }

		var format;
		try { format = new Intl.NumberFormat(document.documentElement.lang || undefined); } catch (e) { format = null; }

		var vus = new IntersectionObserver(function(entrees) {
			entrees.forEach(function(entree) {
				if (!entree.isIntersecting) { return; }
				vus.unobserve(entree.target);

				var cible = parseInt(entree.target.getAttribute('data-valeur'), 10) || 0;
				var final = entree.target.textContent;
				var debut = null;

				(function pas(t) {
					if (debut === null) { debut = t; }
					var f = Math.min(1, (t - debut) / 1200);
					var n = Math.round(cible * (1 - Math.pow(1 - f, 3)));
					entree.target.textContent = f < 1 ? (format ? format.format(n) : String(n)) : final;
					if (f < 1) { window.requestAnimationFrame(pas); }
				})(performance.now());
			});
		}, { threshold: 0.4 });

		nombres.forEach(function(n) { vus.observe(n); });
	}

	// ── L'ombre de la barre ─────────────────────────────────────────────────────────────────────────────
	function ombre() {
		var prevu = false;

		function suivre() {
			document.documentElement.classList.toggle('pl-defile', (window.pageYOffset || document.documentElement.scrollTop) > 6);
			prevu = false;
		}

		window.addEventListener('scroll', function() {
			if (!prevu) { window.requestAnimationFrame(suivre); prevu = true; }
		}, { passive: true });
		suivre();
	}

	function init() {
		menu();
		chiffres();
		ombre();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
