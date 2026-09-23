/**
 * Effet saisonnier : neige, confettis ou feuilles.
 *
 * JavaScript vanilla, comme tout le front du produit depuis la sortie de jQuery. Les paramètres
 * viennent d'attributs `data-` posés par le gabarit, jamais d'un script en ligne : la politique de
 * sécurité du site n'autorise pas l'inline.
 *
 * Trois précautions, parce qu'une décoration ne doit jamais coûter cher :
 *
 *  - `prefers-reduced-motion` arrête tout AVANT la première image. La feuille masque déjà le canvas,
 *    mais un script qui anime un élément invisible consomme quand même une boucle par image, et cela
 *    se voit sur la batterie d'un portable ;
 *  - l'animation se met en pause quand l'onglet passe à l'arrière-plan (`visibilitychange`) ;
 *  - le canvas est redimensionné au rythme du navigateur, pas à chaque événement de redimensionnement.
 */
(function () {
	'use strict';

	var REDUIT = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/** Les trois effets, décrits par ce qui les distingue vraiment. */
	var EFFETS = {
		snow: {
			couleurs: ['#ffffff', '#e8f4ff', '#d8ecff'],
			taille:   [1.5, 4],
			chute:    [0.3, 1.1],
			derive:   0.6,
			rotation: 0,
			forme:    'rond'
		},
		confetti: {
			couleurs: ['#ff595e', '#ffca3a', '#8ac926', '#1982c4', '#6a4c93'],
			taille:   [3, 7],
			chute:    [1.0, 2.4],
			derive:   1.4,
			rotation: 0.12,
			forme:    'rectangle'
		},
		leaves: {
			couleurs: ['#c1440e', '#e07a1f', '#d9a441', '#8d5524'],
			taille:   [4, 9],
			chute:    [0.5, 1.4],
			derive:   1.1,
			rotation: 0.05,
			forme:    'feuille'
		}
	};

	function hasard(min, max) {
		return min + Math.random() * (max - min);
	}

	function demarrer(canvas) {
		var effet = EFFETS[canvas.getAttribute('data-seasonal-effect')];

		if (!effet) {
			return; // effet inconnu : on ne dessine rien plutôt que de deviner
		}

		var ctx = canvas.getContext && canvas.getContext('2d');

		if (!ctx) {
			return; // très vieux navigateur : la page reste parfaitement utilisable sans l'effet
		}

		var nombre     = parseInt(canvas.getAttribute('data-seasonal-count'), 10) || 80;
		var particules = [];
		var anim       = null;
		var largeur    = 0;
		var hauteur    = 0;

		function dimensionner() {
			// `devicePixelRatio` : sans lui, les flocons sont flous sur un écran dense.
			var ratio = window.devicePixelRatio || 1;

			largeur = canvas.clientWidth;
			hauteur = canvas.clientHeight;

			canvas.width  = Math.floor(largeur * ratio);
			canvas.height = Math.floor(hauteur * ratio);
			ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
		}

		function naitre(enHaut) {
			return {
				x:       Math.random() * largeur,
				y:       enHaut ? -10 : Math.random() * hauteur,
				taille:  hasard(effet.taille[0], effet.taille[1]),
				chute:   hasard(effet.chute[0], effet.chute[1]),
				phase:   Math.random() * Math.PI * 2,
				angle:   Math.random() * Math.PI * 2,
				couleur: effet.couleurs[Math.floor(Math.random() * effet.couleurs.length)]
			};
		}

		function peupler() {
			particules = [];

			for (var i = 0; i < nombre; i++) {
				particules.push(naitre(false));
			}
		}

		function dessiner(p) {
			ctx.fillStyle = p.couleur;

			if (effet.forme === 'rond') {
				ctx.beginPath();
				ctx.arc(p.x, p.y, p.taille / 2, 0, Math.PI * 2);
				ctx.fill();
				return;
			}

			ctx.save();
			ctx.translate(p.x, p.y);
			ctx.rotate(p.angle);

			if (effet.forme === 'feuille') {
				ctx.beginPath();
				ctx.ellipse(0, 0, p.taille, p.taille / 2, 0, 0, Math.PI * 2);
				ctx.fill();
			} else {
				ctx.fillRect(-p.taille / 2, -p.taille / 4, p.taille, p.taille / 2);
			}

			ctx.restore();
		}

		function image() {
			ctx.clearRect(0, 0, largeur, hauteur);

			for (var i = 0; i < particules.length; i++) {
				var p = particules[i];

				p.y     += p.chute;
				p.phase += 0.01;
				p.x     += Math.sin(p.phase) * effet.derive;
				p.angle += effet.rotation;

				// Sortie par le bas : la particule renaît en haut plutôt que d'être recréée, ce qui
				// évite de faire travailler le ramasse-miettes soixante fois par seconde.
				if (p.y - p.taille > hauteur) {
					particules[i] = naitre(true);
				}

				dessiner(p);
			}

			anim = window.requestAnimationFrame(image);
		}

		function jouer() {
			if (anim === null) {
				anim = window.requestAnimationFrame(image);
			}
		}

		function pauser() {
			if (anim !== null) {
				window.cancelAnimationFrame(anim);
				anim = null;
			}
		}

		dimensionner();
		peupler();
		jouer();

		// Redimensionnement : on attend l'image suivante plutôt que de recalculer à chaque pixel.
		var attente = null;

		window.addEventListener('resize', function () {
			if (attente !== null) {
				return;
			}

			attente = window.requestAnimationFrame(function () {
				attente = null;
				dimensionner();
				peupler();
			});
		});

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				pauser();
			} else {
				jouer();
			}
		});
	}

	if (REDUIT) {
		return; // le visiteur a demandé moins d'animations : on n'en lance aucune
	}

	if (window.NF && typeof window.NF.ready === 'function') {
		window.NF.ready(function () {
			Array.prototype.forEach.call(document.querySelectorAll('.nf-seasonal'), demarrer);
		});
	}
})();
