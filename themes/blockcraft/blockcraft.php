<?php
/**
 * https://neofr.ag
 * Blockcraft — le site d'un serveur de jeu de blocs (chantier B ; direction D « Spawn + Inventaire » de la planche du
 * 2026-10-07, celle qui a été choisie). Le monde de Spawn : un paysage en blocs en tête, le nom du serveur dans le ciel et
 * son adresse à copier, des blocs à coins carrés et ombres franches, le pied en roche. Les gestes d'Inventaire : la
 * navigation est une barre d'objets — au centre de l'en-tête à l'ordinateur, collée en bas de l'écran au téléphone —,
 * le forum range ses catégories dans des coffres, l'espace membre est l'écran du personnage. Titres Jersey 10, texte
 * Rubik, chiffres VT323 ; jour « prairie », nuit « ciel étoilé ». Tous les pixels sont dessinés pour le thème
 * (.claude/epreuves/chantiers/blockcraft/pixels.py). LGPLv3.
 *
 * couplage(news): les nouvelles de l'accueil ne se posent que si le module Actualités est installé.
 * couplage(forum): les dernières discussions de l'accueil, et les chiffres et l'activité du forum à côté de ses pages, de même : seulement si le module Forum est installé.
 * couplage(calendar): le prochain rendez-vous, de même : seulement si le module Calendrier est installé.
 * couplage(gallery): les dernières constructions (les photos de la galerie), de même : seulement si le module Galerie est installé.
 */

namespace NF\Themes\Blockcraft;

use NF\NeoFrag\Addons\Theme;

class Blockcraft extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Blockcraft',
			'description' => $this->lang('Thème « Blockcraft », le site d’un serveur de jeu de blocs : un paysage en blocs en tête, le nom du serveur dans le ciel et son adresse à copier, la navigation en barre d’objets (collée en bas de l’écran au téléphone), des blocs à coins carrés, le forum en coffres, l’espace membre en écran du personnage, un pied en roche ; titres Jersey 10, texte Rubik, nuit étoilée au choix du visiteur ; couleurs, adresse du serveur, image de fond et logo réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '2.0.0',
			// Le cœur 1.2.38 : le prochain rendez-vous du calendrier, le site en chiffres et les dernières photos.
			'depends' => [
				'neofrag' => '1.2.38'
			],
			// Les zones se lisent par leur RANG (0 à 4) ; leurs noms se traduisent (langs/*.php).
			'zones'       => ['Barre d’objets', 'Contenu', 'Colonne', 'Pleine largeur', 'Pied de page'],
			'regions'     => [
				'navigation' => 'Barre d’objets',
				'content'    => 'Contenu',
				'colonne'    => 'Colonne',
				'largeur'    => 'Pleine largeur',
				'footer'     => 'Pied de page',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
				->css('fonts/jersey-10') // les polices, servies par le site (tools/polices-locales.php)
				->css('fonts/rubik')
				->css('fonts/vt323')
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
				->js('blockcraft');
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
		$this	->config('blockcraft_background',            0,             'int')
				->config('blockcraft_background_repeat',     'repeat')
				->config('blockcraft_background_attachment', 'scroll')
				->config('blockcraft_background_position',   'center top')
				->config('blockcraft_background_color',      '#eef3e6')
				->config('blockcraft_logo',                  0,             'int')
				->config('blockcraft_theme_color',           '#3c7a27')
				->config('blockcraft_text_color',            '#1e2916')
				// L'adresse du serveur, dans le ciel de l'accueil, avec son bouton « Copier l'adresse » (vide : pas d'adresse).
				->config('blockcraft_adresse',               '');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait son bloc vide et sa ligne de nf_widgets orpheline : chaque bloc ne se
		// pose que si son module est là ; les affichages de la 1.2.38, que si leur widget l'est.
		$present = function (string $module): bool {
			return ($addon = @NeoFrag()->module($module)) && $addon->is_enabled();
		};
		$widget_present = function (string $widget): bool {
			return ($addon = @NeoFrag()->widget($widget)) && $addon->is_enabled();
		};
		$bloc = function (string $nom, string $type, ?string $style = NULL, array $reglages = []) {
			$widget = $this->widget($this->db->insert('nf_widgets', [
				'widget'   => $nom,
				'type'     => $type,
				'settings' => $reglages ? \NF\NeoFrag\Fields\Json::encode($reglages) : NULL
			]));

			return $style ? $widget->style($style) : $widget;
		};
		$liens = function (array $liens): array {
			return array_map(static fn (array $l): array => ['title' => utf8_htmlentities($l[0]), 'url' => $l[1]], $liens);
		};

		// La barre d'objets : les rubriques du site, chacune dans sa case, avec l'objet qui la représente (la feuille le
		// choisit d'après l'adresse : le livre pour les nouvelles, la carte pour le forum…).
		$dispositions->set('*', 'Barre d’objets', $this->array([
			$this->row($this->col($bloc('navigation', 'index', NULL, ['links' => $liens([
				[$this->lang('Accueil'),    ''],
				[$this->lang('Nouvelles'),  'news'],
				[$this->lang('Forum'),      'forum'],
				[$this->lang('Agenda'),     'calendar'],
				[$this->lang('Galerie'),    'gallery'],
				[$this->lang('Membres'),    'members'],
				[$this->lang('Mon espace'), 'user']
			]), 'panel' => 0])))->style('row-default')
		]));

		// Le contenu : le module partout ; sur l'accueil, les nouvelles et les dernières discussions ; le fil d'Ariane là
		// où l'on descend dans les pages.
		$dispositions->set('*', 'Contenu', $this->array([
			$this->row($this->col($bloc('module', 'index')))->style('row-default')
		]));

		$accueil = [];

		if ($present('news'))
		{
			$accueil[] = $this->row($this->col($bloc('news', 'index', 'panel-default')))->style('row-default');
		}

		if ($present('forum'))
		{
			$accueil[] = $this->row($this->col($bloc('forum', 'topics', 'panel-default')))->style('row-default');
		}

		$dispositions->set('/', 'Contenu', $this->array($accueil));

		foreach (['forum/*', 'news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row($this->col($bloc('breadcrumb', 'index')))->style('row-default'),
				$this->row($this->col($bloc('module', 'index')))->style('row-default')
			]));
		}

		// La colonne : rien en général — les pages prennent toute la largeur ; sur l'accueil, le prochain rendez-vous, le
		// site en chiffres et qui joue en ce moment ; à côté du forum, ses chiffres et son activité.
		$dispositions->set('*', 'Colonne', $this->array([]));

		$colonne = [];

		if ($present('calendar'))
		{
			$colonne[] = $bloc('calendar', 'prochain', 'panel-color');
		}

		if ($widget_present('chiffres'))
		{
			$colonne[] = $bloc('chiffres', 'index', 'panel-default');
		}

		$colonne[] = $bloc('members', 'online', 'panel-default');

		$dispositions->set('/', 'Colonne', $this->array([
			$this->row($this->col(...$colonne))->style('row-default')
		]));

		if ($present('forum'))
		{
			$dispositions->set('forum/*', 'Colonne', $this->array([
				$this->row($this->col(
						$bloc('forum', 'statistics', 'panel-color'),
						$bloc('forum', 'activity', 'panel-default')
					))
					->style('row-default')
			]));
		}

		// Pleine largeur : sur l'accueil seulement, les dernières constructions — les photos de la galerie.
		$dispositions->set('*', 'Pleine largeur', $this->array([]));

		if ($present('gallery'))
		{
			$dispositions->set('/', 'Pleine largeur', $this->array([
				$this->row($this->col($bloc('gallery', 'grille', 'panel-default')))->style('row-default')
			]));
		}

		// Le pied : quelques chemins.
		$dispositions->set('*', 'Pied de page', $this->array([
			$this->row($this->col($bloc('navigation', 'vertical', NULL, ['links' => $liens([
				[$this->lang('Nouvelles'), 'news'],
				[$this->lang('Forum'),     'forum'],
				[$this->lang('Membres'),   'members'],
				[$this->lang('Contact'),   'contact']
			]), 'panel' => 0])))->style('row-default')
		]));

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->blockcraft_background)->delete();
		NeoFrag()->model2('file', $this->config->blockcraft_logo)->delete();

		foreach ([
			'blockcraft_background', 'blockcraft_background_repeat', 'blockcraft_background_attachment',
			'blockcraft_background_position', 'blockcraft_background_color', 'blockcraft_logo',
			'blockcraft_theme_color', 'blockcraft_text_color', 'blockcraft_adresse',
			// Les réglages de Blockcraft 1.x : la bannière, sa couleur, la barre fixe.
			'blockcraft_header', 'blockcraft_header_repeat', 'blockcraft_header_attachment', 'blockcraft_header_position',
			'blockcraft_header_color', 'blockcraft_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
