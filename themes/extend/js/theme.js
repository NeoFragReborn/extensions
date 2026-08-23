/**
 * Extend theme — bascule nuit/jour.
 * - nuit (navy) par défaut ; jour via le bouton .theme-toggle
 * - localStorage 'nf-extend-theme'
 */
(function() {
	'use strict';

	var STORAGE_KEY = 'nf-extend-theme';

	function getStored() { try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; } }
	function setStored(v) { try { localStorage.setItem(STORAGE_KEY, v); } catch (e) {} }

	function applyTheme(t) { document.documentElement.setAttribute('data-theme', t); document.documentElement.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light'); }

	function syncButton(t) {
		document.querySelectorAll('.theme-toggle').forEach(function(btn) {
			var icon = btn.querySelector('i');
			if (!icon) return;
			if (t === 'dark') {
				icon.className = 'fas fa-sun';
				btn.setAttribute('title', 'Mode jour');
				btn.setAttribute('aria-label', 'Mode jour');
			} else {
				icon.className = 'fas fa-moon';
				btn.setAttribute('title', 'Mode nuit');
				btn.setAttribute('aria-label', 'Mode nuit');
			}
		});
	}

	function toggleTheme() {
		var current = document.documentElement.getAttribute('data-theme') || 'dark';
		var next = current === 'dark' ? 'light' : 'dark';
		document.documentElement.classList.add('no-transitions');
		applyTheme(next);
		setStored(next);
		syncButton(next);
		requestAnimationFrame(function() {
			requestAnimationFrame(function() { document.documentElement.classList.remove('no-transitions'); });
		});
	}

	// Nuit par défaut (sauf choix utilisateur explicite)
	applyTheme(getStored() || 'dark');

	function init() {
		syncButton(document.documentElement.getAttribute('data-theme') || 'dark');

		document.querySelectorAll('.theme-toggle').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				toggleTheme();
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
