/**
 * Carte des lieux.
 *
 * La page est écrite pour être utile SANS ce script : la liste des lieux, leurs adresses et leurs
 * liens vers OpenStreetMap sont déjà dans le HTML. Ce fichier ne fait qu'ajouter la carte par-dessus.
 * C'est pourquoi le conteneur de carte arrive `hidden` et n'est révélé qu'une fois Leaflet en place :
 * un cadre gris vide serait pire que pas de carte du tout.
 *
 * Aucune donnée n'est passée par un script en ligne — la politique de sécurité du site ne l'autorise
 * pas. Tout vient d'attributs `data-` posés par le gabarit et relus ici.
 *
 * Les marqueurs sont des `divIcon` portant une icône FontAwesome, déjà embarquée dans le produit.
 * C'est ce qui permet de n'embarquer AUCUNE des images de Leaflet.
 */
(function () {
	'use strict';

	/** Les tuiles d'OpenStreetMap. Leur usage impose de citer les contributeurs, ce que fait le gabarit. */
	var TUILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

	/** Au-delà, les tuiles n'existent pas. */
	var ZOOM_MAX = 19;

	function nombre(valeur) {
		var n = parseFloat(valeur);
		return isFinite(n) ? n : null;
	}

	function marqueur(item) {
		var lat = nombre(item.getAttribute('data-place-lat'));
		var lon = nombre(item.getAttribute('data-place-lon'));

		if (lat === null || lon === null) {
			return null;   // une ligne sans coordonnées lisibles n'a pas de place sur la carte
		}

		var couleur = item.getAttribute('data-place-color') || '';
		var icone   = item.getAttribute('data-place-icon') || 'fas fa-map-marker-alt';

		// `textContent` puis `innerHTML` du conteneur : on construit l'élément par le DOM plutôt que
		// par concaténation de chaînes, pour que ni le titre ni la classe d'icône ne puissent être
		// interprétés comme du balisage.
		var pastille = document.createElement('span');
		pastille.className = 'nf-places-pin';

		if (/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i.test(couleur)) {
			// Le serveur a déjà restreint la couleur à l'hexadécimal ; on le revérifie ici, parce que
			// ce qui arrive du DOM peut avoir été modifié par une extension de navigateur.
			pastille.style.backgroundColor = couleur;
		}

		var glyphe = document.createElement('i');
		glyphe.className = /^fa[bsrld] fa-[a-z0-9-]+$/.test(icone) ? icone : 'fas fa-map-marker-alt';
		pastille.appendChild(glyphe);

		return {
			lat: lat,
			lon: lon,
			titre: item.getAttribute('data-place-title') || '',
			html: pastille.outerHTML,
			item: item
		};
	}

	function demarrer(bloc) {
		if (typeof window.L === 'undefined') {
			return;   // la bibliothèque n'a pas pu être chargée : la liste reste, et elle suffit
		}

		var conteneur = bloc.querySelector('.nf-places-map');
		var items     = bloc.querySelectorAll('.nf-places-item');

		if (!conteneur || !items.length) {
			return;
		}

		var lieux = [];

		Array.prototype.forEach.call(items, function (item) {
			var m = marqueur(item);
			if (m) { lieux.push(m); }
		});

		if (!lieux.length) {
			return;
		}

		conteneur.hidden = false;

		var carte = window.L.map(conteneur, {
			center: [nombre(bloc.getAttribute('data-places-lat')) || 0, nombre(bloc.getAttribute('data-places-lon')) || 0],
			zoom:   parseInt(bloc.getAttribute('data-places-zoom'), 10) || 13,
			scrollWheelZoom: false   // une carte qui happe la molette empêche de faire défiler la page
		});

		window.L.tileLayer(TUILES, {
			maxZoom: ZOOM_MAX,
			attribution: bloc.getAttribute('data-places-attribution') || ''
		}).addTo(carte);

		var couches = [];

		lieux.forEach(function (lieu) {
			var icone = window.L.divIcon({
				className: 'nf-places-divicon',
				html: lieu.html,
				iconSize: [28, 28],
				iconAnchor: [14, 28],
				popupAnchor: [0, -26]
			});

			var couche = window.L.marker([lieu.lat, lieu.lon], { icon: icone, title: lieu.titre }).addTo(carte);

			// `bindPopup` avec un ÉLÉMENT et non une chaîne : le titre passe par `textContent`, donc
			// jamais interprété comme du balisage.
			var bulle = document.createElement('div');
			bulle.className = 'nf-places-popup';
			bulle.textContent = lieu.titre;
			couche.bindPopup(bulle);

			couches.push(couche);

			// Cliquer une entrée de la liste amène la carte sur le lieu : c'est ce qui relie les deux
			// moitiés de la page.
			lieu.item.addEventListener('click', function (e) {
				if (e.target.closest('a')) {
					return;   // un lien reste un lien
				}

				carte.setView([lieu.lat, lieu.lon], Math.max(carte.getZoom(), 15));
				couche.openPopup();
			});
		});

		// Le cadrage calculé côté serveur sert de valeur de départ ; Leaflet affine avec la taille
		// réelle du conteneur, que le serveur ne peut pas connaître.
		if (couches.length > 1) {
			carte.fitBounds(window.L.featureGroup(couches).getBounds(), { padding: [30, 30], maxZoom: 16 });
		}

		// La molette ne zoome qu'une fois la carte cliquée : on rend le geste possible sans le subir.
		carte.on('click', function () { carte.scrollWheelZoom.enable(); });
		carte.on('mouseout', function () { carte.scrollWheelZoom.disable(); });
	}

	if (window.NF && typeof window.NF.ready === 'function') {
		window.NF.ready(function () {
			Array.prototype.forEach.call(document.querySelectorAll('.nf-places'), demarrer);
		});
	}
})();
