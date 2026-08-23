/**
 * Blockcraft theme — bascule jour/nuit.
 * - jour (clair, biome) par défaut ; nuit (grotte) via le bouton .theme-toggle
 * - localStorage 'nf-blockcraft-theme', suit la préférence système sans choix manuel
 */
(function() {
	'use strict';

	var STORAGE_KEY = 'nf-blockcraft-theme';

	function getStored() { try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; } }
	function setStored(v) { try { localStorage.setItem(STORAGE_KEY, v); } catch (e) {} }
	function sysDark() { return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches; }

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
		var current = document.documentElement.getAttribute('data-theme') || 'light';
		var next = current === 'dark' ? 'light' : 'dark';
		document.documentElement.classList.add('no-transitions');
		applyTheme(next);
		setStored(next);
		syncButton(next);
		requestAnimationFrame(function() {
			requestAnimationFrame(function() { document.documentElement.classList.remove('no-transitions'); });
		});
	}

	applyTheme(getStored() || (sysDark() ? 'dark' : 'light'));

	function init() {
		syncButton(document.documentElement.getAttribute('data-theme') || 'light');

		document.querySelectorAll('.theme-toggle').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				toggleTheme();
			});
		});

		if (!getStored() && window.matchMedia) {
			var mql = window.matchMedia('(prefers-color-scheme: dark)');
			var handler = function(e) {
				if (getStored()) return;
				var t = e.matches ? 'dark' : 'light';
				applyTheme(t);
				syncButton(t);
			};
			if (mql.addEventListener) mql.addEventListener('change', handler);
			else if (mql.addListener) mql.addListener(handler);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
