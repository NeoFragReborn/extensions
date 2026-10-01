// Le Blog (2026-10-01) : la grille ou les lignes, au choix du visiteur et retenu dans son
// navigateur ; la barre de progression de lecture ; le sommaire qui suit la partie en cours.
(function () {
	'use strict';

	// ── Grille ou lignes ────────────────────────────────────────────────
	var billets = document.querySelector('[data-blog-billets]');
	var boutons = document.querySelectorAll('[data-blog-vue]');

	function vue(choix) {
		if (!billets) {
			return;
		}
		billets.classList.toggle('blog-billets-lignes', choix === 'lignes');
		boutons.forEach(function (b) {
			b.setAttribute('aria-pressed', b.dataset.blogVue === choix ? 'true' : 'false');
		});
	}

	if (billets && boutons.length) {
		var memoire;
		try { memoire = localStorage.getItem('nf-blog-vue'); } catch (e) { memoire = null; }
		vue(memoire === 'lignes' ? 'lignes' : 'grille');

		boutons.forEach(function (b) {
			b.addEventListener('click', function () {
				vue(b.dataset.blogVue);
				try { localStorage.setItem('nf-blog-vue', b.dataset.blogVue); } catch (e) { /* navigation privée */ }
			});
		});
	}

	// ── Progression de lecture ──────────────────────────────────────────
	var barre = document.querySelector('[data-blog-progression]');
	var contenu = document.querySelector('[data-blog-contenu]');

	if (barre && contenu) {
		var maj = function () {
			var r = contenu.getBoundingClientRect();
			var total = r.height - window.innerHeight;
			var fait = total > 0 ? Math.min(1, Math.max(0, -r.top / total)) : (r.top < 0 ? 1 : 0);
			barre.style.width = (fait * 100).toFixed(1) + '%';
		};
		window.addEventListener('scroll', maj, { passive: true });
		window.addEventListener('resize', maj);
		maj();
	}

	// ── Le sommaire suit la partie en cours ─────────────────────────────
	var liens = document.querySelectorAll('.blog-sommaire a[href^="#"]');

	if (liens.length && 'IntersectionObserver' in window) {
		var parId = {};
		liens.forEach(function (a) { parId[decodeURIComponent(a.getAttribute('href').slice(1))] = a.closest('li'); });

		var observateur = new IntersectionObserver(function (entrees) {
			entrees.forEach(function (e) {
				if (e.isIntersecting && parId[e.target.id]) {
					Object.keys(parId).forEach(function (id) { parId[id].classList.remove('actif'); });
					parId[e.target.id].classList.add('actif');
				}
			});
		}, { rootMargin: '0px 0px -70% 0px' });

		Object.keys(parId).forEach(function (id) {
			var titre = document.getElementById(id);
			if (titre) {
				observateur.observe(titre);
			}
		});
	}
}());
