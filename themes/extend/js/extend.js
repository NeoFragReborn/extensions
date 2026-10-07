/**
 * Extend 2.0.0 « Lanceur » — les gestes du thème, en JavaScript maison (aucune bibliothèque) :
 * - au téléphone, la barre d'onglets du bas : « En ligne » y ouvre le panneau de droite (en tiroir), avec le nombre de
 *   membres connectés ; au-delà de quatre rubriques, les dernières passent dans « Plus », une feuille au-dessus de la
 *   barre. Sans script, rien ne se perd : les onglets défilent, et le panneau suit la page ;
 * - le tiroir se ferme par sa croix, par le voile, par Échap ; le focus va et revient.
 * L'ancien bouton « retour en haut » n'est plus : au téléphone, il aurait couvert la barre d'onglets.
 */
(function() {
	'use strict';

	document.documentElement.classList.add('ex-js');

	var LARGE = window.matchMedia ? window.matchMedia('(min-width: 992px)') : null;

	function element(nom, classe, texte) {
		var e = document.createElement(nom);
		if (classe) { e.className = classe; }
		if (texte) { e.textContent = texte; }
		return e;
	}

	// Un onglet du téléphone : son pictogramme (Font Awesome), puis son nom.
	function onglet(classe, icone, nom) {
		var b = element('button', classe);
		var i = element('i', 'icon ' + icone);
		b.type = 'button';
		i.setAttribute('aria-hidden', 'true');
		b.appendChild(i);
		b.appendChild(document.createTextNode(' '));
		b.appendChild(element('span', '', nom));
		return b;
	}

	function suivreLargeur(rappel) {
		if (!LARGE) { return; }
		var f = function() { if (LARGE.matches) { rappel(); } };
		if (LARGE.addEventListener) { LARGE.addEventListener('change', f); } else if (LARGE.addListener) { LARGE.addListener(f); }
	}

	// ── Le panneau de droite, en tiroir au téléphone ─────────────────────────────────────────────────────
	function tiroir() {
		var panneau = document.getElementById('ex-panneau');
		if (!panneau) { return null; }

		var voile = null;
		var retour = null;

		function fermer() {
			panneau.classList.remove('is-ouvert');
			if (voile) { voile.remove(); voile = null; }
			document.querySelectorAll('[aria-controls="ex-panneau"]').forEach(function(b) { b.setAttribute('aria-expanded', 'false'); });
			if (retour) { retour.focus(); retour = null; }
		}

		function ouvrir(depuis) {
			retour = depuis || null;
			panneau.classList.add('is-ouvert');
			voile = element('div', 'ex-voile');
			voile.addEventListener('click', fermer);
			document.body.appendChild(voile);
			document.querySelectorAll('.ex-tel-en-ligne[aria-controls="ex-panneau"]').forEach(function(b) { b.setAttribute('aria-expanded', 'true'); });
			var croix = panneau.querySelector('.ex-panneau-fermer');
			if (croix) { croix.focus(); }
		}

		var croix = panneau.querySelector('.ex-panneau-fermer');
		if (croix) { croix.addEventListener('click', fermer); }
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && panneau.classList.contains('is-ouvert')) { fermer(); }
		});
		// Sur un écran large, le panneau est une colonne : plus de tiroir à fermer.
		suivreLargeur(function() { if (panneau.classList.contains('is-ouvert')) { fermer(); } });

		return { ouvrir: ouvrir, fermer: fermer, ouvert: function() { return panneau.classList.contains('is-ouvert'); } };
	}

	// ── La barre d'onglets du téléphone : « En ligne », puis « Plus » s'il le faut ──────────────────────
	function barre(leTiroir) {
		var nav = document.querySelector('.ex-onglets .widget-navigation .nav');
		if (!nav) { return; }

		var entrees = Array.prototype.filter.call(nav.children, function(li) { return li.classList.contains('nav-item'); });
		var places = leTiroir ? 4 : 5;

		if (leTiroir) {
			// Le nombre de membres connectés, lu dans le panneau (administrateurs et membres).
			var nombres = document.querySelectorAll('#ex-panneau .nf-en-ligne-chiffres b');
			var connectes = 0;
			for (var i = 0; i < nombres.length && i < 2; i++) { connectes += parseInt(nombres[i].textContent, 10) || 0; }

			var item = element('li', 'nav-item ex-tel-seul');
			var bouton = onglet('nav-link ex-tel-en-ligne', 'fas fa-user-group', '<?php echo addslashes($this->lang('En ligne')) ?>');
			if (connectes) { bouton.appendChild(element('span', 'ex-compteur', String(connectes))); }
			bouton.setAttribute('aria-controls', 'ex-panneau');
			bouton.setAttribute('aria-expanded', 'false');
			bouton.style.position = 'relative';
			bouton.addEventListener('click', function() {
				if (leTiroir.ouvert()) { leTiroir.fermer(); } else { leTiroir.ouvrir(bouton); }
			});
			item.appendChild(bouton);

			if (entrees.length <= places) {
				nav.appendChild(item);
				return;
			}
		}

		if (entrees.length <= places) { return; }

		// Plus de rubriques que de places : les premières restent, les autres passent dans la feuille « Plus ».
		var gardees = places - 1;
		var debordes = entrees.slice(gardees);
		var feuille = element('div', 'ex-plus-feuille');
		var liste = element('ul');
		var actif = false;

		debordes.forEach(function(li) {
			li.classList.add('ex-deborde');
			var copie = li.cloneNode(true);
			copie.classList.remove('ex-deborde');
			liste.appendChild(copie);
			if (li.querySelector('.nav-link.active')) { actif = true; }
		});

		feuille.id = 'ex-plus-feuille';
		feuille.hidden = true;
		feuille.appendChild(liste);
		document.body.appendChild(feuille);

		var plus = element('li', 'nav-item ex-tel-seul');
		var boutonPlus = onglet('nav-link' + (actif ? ' active' : ''), 'fas fa-ellipsis', '<?php echo addslashes($this->lang('Plus')) ?>');
		boutonPlus.setAttribute('aria-expanded', 'false');
		boutonPlus.setAttribute('aria-controls', feuille.id);
		plus.appendChild(boutonPlus);

		if (leTiroir) {
			nav.insertBefore(item, debordes[0]);
		}
		nav.appendChild(plus);

		function fermer() {
			feuille.hidden = true;
			boutonPlus.setAttribute('aria-expanded', 'false');
		}

		boutonPlus.addEventListener('click', function(e) {
			e.stopPropagation();
			feuille.hidden = !feuille.hidden;
			boutonPlus.setAttribute('aria-expanded', feuille.hidden ? 'false' : 'true');
		});
		document.addEventListener('click', function(e) {
			if (!feuille.hidden && !feuille.contains(e.target)) { fermer(); }
		});
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && !feuille.hidden) { fermer(); boutonPlus.focus(); }
		});
		suivreLargeur(fermer);
	}

	function init() {
		barre(tiroir());
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
