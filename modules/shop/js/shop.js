/**
 * Boutique — achat AJAX. Le bouton porte data-buy-url (URL résolue côté PHP).
 */
(function() {
	'use strict';

	function buy(btn) {
		if (btn.disabled) return;
		var url = btn.getAttribute('data-buy-url');
		if (!url) return;

		btn.disabled = true;
		var original = btn.innerHTML;
		btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

		fetch(url, {
			method: 'POST',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			credentials: 'same-origin'
		})
		.then(function(r) { return r.json(); })
		.then(function(d) {
			if (d && d.ok) {
				var card = btn.closest('[data-shop-item]');
				if (card) {
					var foot = card.querySelector('.card-footer');
					if (foot) foot.innerHTML = '<span class="btn btn-sm btn-success disabled"><i class="fas fa-check"></i> <?php echo addslashes($this->lang('Possédé')) ?></span>';
				}
				var bal = document.querySelector('[data-shop-balance]');
				if (bal && typeof d.balance !== 'undefined') bal.textContent = d.balance;
			} else {
				btn.disabled = false;
				btn.innerHTML = original;
				var msg = '<?php echo addslashes($this->lang('Achat impossible.')) ?>';
				if (d) {
					if (d.error === 'insufficient') msg = '<?php echo addslashes($this->lang('Points insuffisants.')) ?>';
					else if (d.error === 'owned') msg = '<?php echo addslashes($this->lang('Tu possèdes déjà cet item.')) ?>';
					else if (d.error === 'login') msg = '<?php echo addslashes($this->lang('Connecte-toi pour acheter.')) ?>';
					else if (d.error === 'stock') msg = '<?php echo addslashes($this->lang('Stock épuisé.')) ?>';
					else if (d.error === 'csrf') msg = '<?php echo addslashes($this->lang('La page a expiré : recharge-la, puis réessaie.')) ?>';
				}
				window.alert(msg);
			}
		})
		.catch(function() {
			btn.disabled = false;
			btn.innerHTML = original;
		});
	}

	function init() {
		document.querySelectorAll('[data-shop-buy]').forEach(function(btn) {
			btn.addEventListener('click', function() { buy(btn); });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
