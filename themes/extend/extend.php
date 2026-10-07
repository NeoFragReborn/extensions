<?php
/**
 * https://neofr.ag
 * Extend 2.0.0 « Lanceur » (chantier B ; direction A de la planche du 2026-10-07, celle qui a été choisie) : le site
 * se présente comme le lanceur d'un jeu en ligne. Une barre d'onglets en haut, une grande vitrine sur l'accueil (le
 * diaporama, ou l'image réglable du thème), la page au centre, le panneau des membres en ligne et de la discussion
 * toujours ouvert à droite, une barre d'état en bas (serveur de jeu, salon vocal, langue). Au téléphone, les onglets
 * passent en bas de l'écran et le panneau s'ouvre par son onglet « En ligne ». Bleu acier sur fond marine, nuit par
 * défaut ; titres Saira Condensed, texte Albert Sans, chiffres JetBrains Mono.
 * D'après le thème « Extend » de Chewbaka (CC BY-NC-SA 4.0), dont il garde le nom, la licence et l'esprit.
 *
 * couplage(slider): la vitrine de l'accueil ne se pose que si le module Diaporama est installé (sinon le thème la dessine lui-même).
 * couplage(calendar): les prochains rendez-vous de l'accueil, de même : seulement si le module Calendrier est installé.
 * couplage(news): les actualités de l'accueil, de même : seulement si le module Actualités est installé.
 * couplage(events): les derniers résultats de l'accueil, de même : seulement si le module Événements est installé.
 * couplage(talks): le salon du panneau de droite, de même : seulement si le module Discussions est installé.
 * couplage(forum): les chiffres et l'activité du forum, à côté de ses pages, de même : seulement si le module Forum est installé.
 */

namespace NF\Themes\Extend;

use NF\NeoFrag\Addons\Theme;

class Extend extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Extend',
			'description' => $this->lang('Thème « Extend », le site comme le lanceur d’un jeu en ligne : une barre d’onglets, une grande vitrine sur l’accueil, les membres en ligne et la discussion toujours ouverts à droite, une barre d’état en bas (serveur de jeu, salon vocal, langue) ; au téléphone, les onglets en bas de l’écran. Bleu acier sur fond marine, nuit par défaut ; logo, image de la vitrine, fond et couleurs réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Chewbaka — portage NeoFrag Reborn',
			'license'     => 'Creative Commons CC BY-NC-SA 4.0 <https://creativecommons.org/licenses/by-nc-sa/4.0/>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '2.0.0',
			// Le cœur 1.2.42 : la liste des membres en ligne et les derniers messages d'un salon (les widgets du panneau).
			'depends' => [
				'neofrag' => '1.2.42'
			],
			// Les zones se lisent par leur RANG (0 à 4) ; leurs noms se traduisent (langs/*.php).
			'zones'       => ['Onglets', 'Vitrine', 'Contenu', 'En ligne', 'Barre d’état'],
			'regions'     => [
				'navigation' => 'Onglets',
				'vitrine'    => 'Vitrine',
				'content'    => 'Contenu',
				'dock'       => 'En ligne',
				'etat'       => 'Barre d’état',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
				// Le socle commun des thèmes (chantier B, étape B1) : AVANT la feuille du thème, qui peut tout redéfinir.
				->css('nf-socle-themes')
				->css('style')
				// APRES la feuille du theme, et jamais avant : voir css/nf-apres-theme.css.
				->css('nf-apres-theme')
				->js('bootstrap.bundle.min')
				->js('modal')
				->js('notify')
				->js('confirm')
				->js('theme')
				->js('extend');
	}

	public function styles_row()
	{
		return $this->view('live_editor/row');
	}

	public function styles_widget()
	{
		return $this->view('live_editor/widget');
	}

	public function install($dispositions = [])
	{
		$this	->config('extend_background',            0,             'int')
				->config('extend_background_repeat',     'repeat')
				->config('extend_background_attachment', 'scroll')
				->config('extend_background_position',   'center top')
				->config('extend_background_color',      '#0a111c')
				// L'image de la vitrine : derrière le nom du site sur l'accueil (quand il n'y a pas de diaporama) et
				// derrière le titre des autres pages. Vide : un paysage de nuit dessiné par le thème.
				->config('extend_header',                0,             'int')
				->config('extend_header_position',       'center center')
				->config('extend_logo',                  0,             'int')
				->config('extend_theme_color',           '#236daf')
				->config('extend_text_color',            '#c3d0de');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait son bloc vide et sa ligne de nf_widgets orpheline : chaque bloc ne se
		// pose que si son module est là.
		$present = function (string $module): bool {
			return ($addon = @NeoFrag()->module($module)) && $addon->is_enabled();
		};
		$bloc = function (string $nom, string $type, ?string $style = NULL, array $reglages = []) {
			$widget = $this->widget($this->db->insert('nf_widgets', [
				'widget'   => $nom,
				'type'     => $type,
				'settings' => $reglages ? \NF\NeoFrag\Fields\Json::encode($reglages) : NULL
			]));

			return $style ? $widget->style($style) : $widget;
		};
		$rangee = function (...$colonnes) {
			return $this->row(...$colonnes)->style('row-default');
		};

		// Les onglets : les rubriques du site, chacune avec son pictogramme (montré au téléphone, dans la barre du bas).
		$liens = [];

		foreach ([
			[$this->lang('Accueil'),    '',         'fas fa-house'],
			[$this->lang('Actualités'), 'news',     'far fa-newspaper'],
			[$this->lang('Forum'),      'forum',    'far fa-comments'],
			[$this->lang('Équipes'),    'teams',    'fas fa-users'],
			[$this->lang('Galerie'),    'gallery',  'far fa-images'],
			[$this->lang('Agenda'),     'calendar', 'far fa-calendar']
		] as [$titre, $url, $icone])
		{
			$liens[] = ['title' => utf8_htmlentities($titre), 'url' => $url, 'icon' => $icone];
		}

		$dispositions->set('*', 'Onglets', $this->array([
			$rangee($this->col($bloc('navigation', 'index', NULL, ['links' => $liens, 'panel' => 0])))
		]));

		// La vitrine de l'accueil : le diaporama. Sans lui, le thème dessine la sienne (le nom du site sur son image).
		$dispositions->set('*', 'Vitrine', $this->array([]));

		if ($present('slider'))
		{
			$dispositions->set('/', 'Vitrine', $this->array([
				$rangee($this->col($bloc('slider', 'index')))
			]));
		}

		// Le contenu : le module partout ; sur l'accueil, les prochains rendez-vous, les actualités et les derniers
		// résultats, chacun sur sa rangée (la feuille les range en cartes et en vignettes) ; le fil d'Ariane là où
		// l'on descend dans les pages.
		$dispositions->set('*', 'Contenu', $this->array([
			$rangee($this->col($bloc('module', 'index')))
		]));

		$accueil = [];

		if ($present('calendar'))
		{
			$accueil[] = $rangee($this->col($bloc('calendar', 'upcoming', 'panel-default', ['count' => 3, 'display_panel' => 'oui'])));
		}

		if ($present('news'))
		{
			$accueil[] = $rangee($this->col($bloc('news', 'index', 'panel-default')));
		}

		if ($present('events'))
		{
			$accueil[] = $rangee($this->col($bloc('events', 'matches', 'panel-default')));
		}

		$dispositions->set('/', 'Contenu', $this->array($accueil));

		foreach (['forum/*', 'news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$rangee($this->col($bloc('breadcrumb', 'index'))),
				$rangee($this->col($bloc('module', 'index')))
			]));
		}

		// Le panneau de droite, sur toutes les pages : qui est en ligne, puis le salon de discussion ; à côté du forum,
		// ses chiffres à la place du salon.
		$panneau = [$bloc('members', 'en_ligne', 'panel-default')];

		if ($present('talks'))
		{
			$panneau[] = $bloc('talks', 'salon', 'panel-default');
		}

		$dispositions->set('*', 'En ligne', $this->array([
			$rangee($this->col(...$panneau))
		]));

		if ($present('forum'))
		{
			$dispositions->set('forum/*', 'En ligne', $this->array([
				$rangee($this->col(
					$bloc('members', 'en_ligne', 'panel-default'),
					$bloc('forum', 'statistics', 'panel-default')
				))
			]));
		}

		// La barre d'état : vide à l'installation — c'est la place des widgets « Serveur de jeu », « TeamSpeak » et
		// « Discord », une fois réglés ; la langue et le mode jour/nuit y sont toujours.
		$dispositions->set('*', 'Barre d’état', $this->array([]));

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->extend_background)->delete();
		NeoFrag()->model2('file', $this->config->extend_header)->delete();
		NeoFrag()->model2('file', $this->config->extend_logo)->delete();

		foreach ([
			'extend_background', 'extend_background_repeat', 'extend_background_attachment',
			'extend_background_position', 'extend_background_color',
			'extend_header', 'extend_header_position', 'extend_logo', 'extend_theme_color', 'extend_text_color',
			// Les réglages d'Extend 1.x : la couleur, la répétition et la fixité de la bannière, la barre fixe.
			'extend_header_repeat', 'extend_header_attachment', 'extend_header_color', 'extend_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
