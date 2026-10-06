/**
 * Forge 2.0 « Coulée » — les gestes du thème, en JavaScript maison (aucune bibliothèque) :
 * - « Plus » : au téléphone, la navigation du rail devient une barre d'onglets ; au-delà de cinq entrées,
 *   les dernières passent dans une feuille qui s'ouvre au-dessus de la barre ;
 * - la jauge de chaleur des forums : chaque forum de la page d'accueil du forum reçoit son activité (sujets
 *   et réponses), comparée au plus actif ; la feuille la dessine (css/style.css, § Forum) ;
 * - le bouton « retour en haut », qui apparaît au défilement.
 * Tout mouvement suit `prefers-reduced-motion`.
 */
(function() {
	'use strict';

	var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// ── « Plus » : les entrées de trop dans la barre d'onglets du téléphone ─────────────────────────────
	function plus() {
		var nav = document.querySelector('.fg-rail-zone .widget-navigation .nav');
		if (!nav) { return; }

		var entrees = Array.prototype.filter.call(nav.children, function(li) { return li.classList.contains('nav-item'); });
		if (entrees.length <= 5) { return; }

		var debordes = entrees.slice(4);
		var feuille  = document.createElement('div');
		var liste    = document.createElement('ul');
		var item     = document.createElement('li');
		var bouton   = document.createElement('button');
		var actif    = false;

		debordes.forEach(function(li) {
			li.classList.add('fg-deborde');
			var copie = li.cloneNode(true);
			copie.classList.remove('fg-deborde');
			liste.appendChild(copie);
			if (li.querySelector('.nav-link.active')) { actif = true; }
		});

		feuille.id = 'fg-plus-feuille';
		feuille.className = 'fg-plus-feuille';
		feuille.hidden = true;
		feuille.appendChild(liste);
		document.body.appendChild(feuille);

		item.className = 'nav-item fg-plus';
		bouton.type = 'button';
		bouton.className = 'nav-link' + (actif ? ' active' : '');
		bouton.setAttribute('aria-expanded', 'false');
		bouton.setAttribute('aria-controls', feuille.id);
		bouton.innerHTML = '<i class="icon fas fa-ellipsis"></i> <?php echo addslashes($this->lang('Plus')) ?>';
		item.appendChild(bouton);
		nav.appendChild(item);

		function fermer() {
			feuille.hidden = true;
			bouton.setAttribute('aria-expanded', 'false');
		}

		bouton.addEventListener('click', function(e) {
			e.stopPropagation();
			feuille.hidden = !feuille.hidden;
			bouton.setAttribute('aria-expanded', feuille.hidden ? 'false' : 'true');
		});
		document.addEventListener('click', function(e) {
			if (!feuille.hidden && !feuille.contains(e.target)) { fermer(); }
		});
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && !feuille.hidden) { fermer(); bouton.focus(); }
		});
		// La feuille n'a pas de sens sur un écran large, où le rail montre tout.
		if (window.matchMedia) {
			var large = window.matchMedia('(min-width: 992px)');
			var suivre = function() { if (large.matches) { fermer(); } };
			if (large.addEventListener) { large.addEventListener('change', suivre); } else if (large.addListener) { large.addListener(suivre); }
		}
	}

	// ── La jauge de chaleur des forums ──────────────────────────────────────────────────────────────────
	function chaleur() {
		var lignes = document.querySelectorAll('.module-forum table[data-forum-view="categories"] > tbody > tr');
		if (!lignes.length) { return; }

		var activites = Array.prototype.map.call(lignes, function(tr) {
			var cellule = tr.children[2];
			var nombres = cellule ? (cellule.textContent.match(/\d+/g) || []) : [];
			return nombres.reduce(function(somme, n) { return somme + parseInt(n, 10); }, 0);
		});
		var max = Math.max.apply(null, activites);

		Array.prototype.forEach.call(lignes, function(tr, i) {
			tr.style.setProperty('--fg-chaleur-forum', max > 0 ? (activites[i] / max).toFixed(3) : '0');
		});
	}

	// ── Retour en haut ──────────────────────────────────────────────────────────────────────────────────
	function haut() {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'fg-to-top';
		btn.setAttribute('aria-label', '<?php echo addslashes($this->lang('Retour en haut')) ?>');
		btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
		document.body.appendChild(btn);

		var attente = false;

		function suivre() {
			btn.classList.toggle('is-visible', window.pageYOffset > 400);
			attente = false;
		}

		window.addEventListener('scroll', function() {
			if (!attente) { window.requestAnimationFrame(suivre); attente = true; }
		}, { passive: true });

		btn.addEventListener('click', function() {
			window.scrollTo({ top: 0, behavior: reduit ? 'auto' : 'smooth' });
		});

		suivre();
	}

	function init() {
		plus();
		chaleur();
		haut();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
