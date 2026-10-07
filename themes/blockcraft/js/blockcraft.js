/**
 * Blockcraft — les gestes du serveur, en JavaScript maison (aucune bibliothèque) :
 * - « Copier l'adresse » : l'adresse du serveur part dans le presse-papiers ; le bouton le dit deux secondes, et une
 *   annonce discrète le dit aux lecteurs d'écran. Sans presse-papiers (page non sécurisée, vieux navigateur), l'adresse
 *   est sélectionnée : il ne reste qu'à la copier soi-même.
 * L'ancien bouton « retour en haut » n'est plus : au téléphone, il aurait couvert la barre d'objets.
 */
(function() {
	'use strict';

	function copier() {
		var bouton = document.querySelector('.bc-copier[data-adresse]');
		if (!bouton) { return; }

		var annonce = document.querySelector('.bc-annonce');
		var libelle = bouton.textContent;
		var minuteur = null;

		function dire(texte) {
			bouton.textContent = texte;
			bouton.classList.add('is-copie');
			if (annonce) { annonce.textContent = texte; }
			window.clearTimeout(minuteur);
			minuteur = window.setTimeout(function() {
				bouton.textContent = libelle;
				bouton.classList.remove('is-copie');
				if (annonce) { annonce.textContent = ''; }
			}, 2000);
		}

		function selectionner() {
			var code = bouton.parentNode.querySelector('code');
			if (!code || !window.getSelection) { return; }
			var plage = document.createRange();
			plage.selectNodeContents(code);
			var selection = window.getSelection();
			selection.removeAllRanges();
			selection.addRange(plage);
		}

		bouton.addEventListener('click', function() {
			var adresse = bouton.getAttribute('data-adresse');
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(adresse).then(function() {
					dire(bouton.getAttribute('data-copie'));
				}, selectionner);
			} else {
				selectionner();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', copier);
	} else {
		copier();
	}
})();
