// « Déjà signalé ? » — pendant qu'on écrit le titre d'un nouveau ticket, les tickets ouverts qui
// lui ressemblent s'affichent sous le champ, pour commenter l'existant plutôt que d'en ouvrir un
// second (2026-10-01). Les textes viennent du gabarit (data-*), donc des traductions du module.
(function () {
	'use strict';

	var boite = document.getElementById('bt-similaires');
	if (!boite) {
		return;
	}

	var champ = document.querySelector('input[id^="form_"][id$="_title"]');
	if (!champ) {
		return;
	}

	var minuteur = null;
	var derniere = '';

	function vider() {
		boite.hidden = true;
		boite.replaceChildren();
	}

	function afficher(tickets) {
		if (!tickets.length) {
			vider();
			return;
		}

		var titre = document.createElement('strong');
		titre.textContent = boite.dataset.titre;

		var aide = document.createElement('p');
		aide.className = 'mb-2 small';
		aide.textContent = boite.dataset.aide;

		var liste = document.createElement('ul');
		liste.className = 'mb-0';

		tickets.forEach(function (t) {
			var item = document.createElement('li');
			var lien = document.createElement('a');
			lien.href = t.url;
			lien.textContent = '#' + t.id + ' — ' + t.title;
			var precision = document.createElement('small');
			precision.className = 'text-muted';
			precision.textContent = ' · ' + t.type + ' · ' + t.status;
			item.append(lien, precision);
			liste.append(item);
		});

		boite.replaceChildren(titre, aide, liste);
		boite.hidden = false;
	}

	function chercher() {
		var q = champ.value.trim();
		if (q === derniere) {
			return;
		}
		derniere = q;

		if (q.length < 3) {
			vider();
			return;
		}

		fetch(boite.dataset.adresse + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
			.then(function (r) { return r.ok ? r.json() : {}; })
			.then(function (d) { afficher(Array.isArray(d.tickets) ? d.tickets : []); })
			.catch(vider);
	}

	champ.insertAdjacentElement('afterend', boite);

	champ.addEventListener('input', function () {
		clearTimeout(minuteur);
		minuteur = setTimeout(chercher, 300);
	});
}());
