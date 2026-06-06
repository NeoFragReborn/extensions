/**
 * Forge theme — interactions légères.
 * - bouton « retour en haut » qui apparaît au défilement (respecte prefers-reduced-motion)
 */
(function() {
	'use strict';

	function init() {
		// Sélecteur de thème (footer) : pose le cookie nf_theme et recharge.
		document.querySelectorAll('[data-theme-pick]').forEach(function(el) {
			el.addEventListener('click', function() {
				document.cookie = 'nf_theme=' + encodeURIComponent(el.getAttribute('data-theme-pick')) + ';path=/;max-age=31536000;samesite=lax';
				location.reload();
			});
		});

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'fg-to-top';
		btn.setAttribute('aria-label', 'Retour en haut');
		btn.innerHTML = '<i class="fas fa-chevron-up"></i>';
		document.body.appendChild(btn);

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var ticking = false;

		function update() {
			btn.classList.toggle('is-visible', window.pageYOffset > 400);
			ticking = false;
		}

		window.addEventListener('scroll', function() {
			if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
		}, { passive: true });

		btn.addEventListener('click', function() {
			window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
		});

		update();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
