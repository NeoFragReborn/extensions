/**
 * Paiements — crée la session Stripe Checkout puis redirige vers la page de paiement.
 */
(function() {
	'use strict';

	function init() {
		document.querySelectorAll('[data-pay-buy]').forEach(function(btn) {
			btn.addEventListener('click', function() {
				if (btn.disabled) return;
				var original = btn.innerHTML;
				btn.disabled = true;
				btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

				fetch(btn.getAttribute('data-url'), {
					method: 'POST',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					credentials: 'same-origin'
				})
				.then(function(r) { return r.json(); })
				.then(function(d) {
					if (d && d.ok && d.url) {
						window.location = d.url;
					} else {
						btn.disabled = false;
						btn.innerHTML = original;
						window.alert(d && d.error === 'disabled' ? 'Paiement indisponible.' : (d && d.error === 'login' ? 'Connecte-toi pour acheter.' : 'Erreur de paiement.'));
					}
				})
				.catch(function() {
					btn.disabled = false;
					btn.innerHTML = original;
				});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
