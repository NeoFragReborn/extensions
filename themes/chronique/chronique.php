<?php
/**
 * https://neofr.ag
 * Chronique — le carnet de la saison, pour les associations et les clubs (chantier B, étape B3 ; maquette C du
 * 2026-10-06, devenue un thème à part entière). Un en-tête discret qui reste en haut, avec un fin
 * trait de progression, un « Sommaire » qui ouvre tout le site et l'appel à adhérer ; l'accueil s'ouvre sur une grande
 * phrase et la semaine en cours, puis une frise raconte la saison mois par mois ; une colonne reste à côté ; le pied
 * ferme le carnet. Titres Fraunces, texte Work Sans, dates IBM Plex Mono ; jour « papier », nuit « à la lampe ». LGPLv3.
 *
 * couplage(calendar): « La semaine » de l'ouverture ne se pose que si le module Calendrier est installé.
 * couplage(downloads): les documents de la colonne, de même : seulement si le module Téléchargements est installé.
 * couplage(partners): les partenaires de la colonne, de même : seulement si le module Partenaires est installé.
 * couplage(forum): les chiffres et l'activité du forum, à côté de ses pages, de même : seulement si le module Forum est installé.
 */

namespace NF\Themes\Chronique;

use NF\NeoFrag\Addons\Theme;

class Chronique extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Chronique',
			'description' => $this->lang('Thème « Chronique », le carnet de la saison des associations et des clubs : un en-tête discret et un « Sommaire » qui ouvre tout le site, une ouverture avec la semaine en cours, une frise qui raconte la saison mois par mois, une colonne à côté, un pied comme la fin d’un livre ; titres Fraunces, texte Work Sans, nuit « à la lampe » au choix du visiteur ; couleurs, image d’ouverture, logo et appel à adhérer réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0.0',
			// Le cœur 1.2.36 : la frise de la saison, la semaine du calendrier, et les composants de Bootstrap en mode
			// jour raccordés au thème (css/nf-socle-themes.css).
			'depends' => [
				'neofrag' => '1.2.36'
			],
			// Les zones se lisent par leur RANG (0 à 4) ; leurs noms se traduisent (langs/*.php).
			'zones'       => ['Sommaire', 'Ouverture', 'Contenu', 'À côté', 'Pied de page'],
			'regions'     => [
				'sommaire'  => 'Sommaire',
				'ouverture' => 'Ouverture',
				'content'   => 'Contenu',
				'cote'      => 'À côté',
				'footer'    => 'Pied de page',
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
				->js('chronique');
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
		$this	->config('chronique_background',            0,             'int')
				->config('chronique_background_repeat',     'repeat')
				->config('chronique_background_attachment', 'scroll')
				->config('chronique_background_position',   'center top')
				->config('chronique_background_color',      '#f6f4ef')
				->config('chronique_header',                0,             'int')
				->config('chronique_header_repeat',         'no-repeat')
				->config('chronique_header_attachment',     'scroll')
				->config('chronique_header_position',       'center center')
				->config('chronique_logo',                  0,             'int')
				->config('chronique_theme_color',           '#0e6f78')
				->config('chronique_text_color',            '#22282c')
				// L'appel à adhérer, en tête : son adresse (vide : l'inscription du site, si elle est ouverte).
				->config('chronique_appel',                 '');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait sa case vide et sa ligne de nf_widgets orpheline : la semaine,
		// les documents, les partenaires et les chiffres du forum ne se posent que si leur module est là ; la frise,
		// que si son widget est installé (à défaut, l'accueil garde le module).
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

		// Le sommaire : tout le site, que le bouton « Sommaire » de l'en-tête ouvre.
		$liens = [];

		foreach ([
			[$this->lang('Accueil'),    ''],
			[$this->lang('Actualités'), 'news'],
			[$this->lang('Agenda'),     'calendar'],
			[$this->lang('Forum'),      'forum'],
			[$this->lang('Galerie'),    'gallery'],
			[$this->lang('Documents'),  'downloads'],
			[$this->lang('Membres'),    'members'],
			[$this->lang('Contact'),    'contact']
		] as [$titre, $url])
		{
			$liens[] = [
				'title' => utf8_htmlentities($titre),
				'url'   => $url
			];
		}

		$dispositions->set('*', 'Sommaire', $this->array([
			$this->row($this->col($bloc('navigation', 'vertical', NULL, ['links' => $liens, 'panel' => 0])))->style('row-default')
		]));

		// L'ouverture de l'accueil : la semaine en cours, à côté de la grande phrase.
		if ($present('calendar'))
		{
			$dispositions->set('/', 'Ouverture', $this->array([
				$this->row($this->col($bloc('calendar', 'semaine', 'panel-default')))->style('row-default')
			]));
		}

		// Le contenu : la frise de la saison sur l'accueil ; le module ailleurs, précédé du fil d'Ariane là où l'on
		// descend dans les pages.
		$dispositions->set('*', 'Contenu', $this->array([
			$this->row($this->col($bloc('module', 'index')))->style('row-default')
		]));

		if ($widget_present('frise'))
		{
			$dispositions->set('/', 'Contenu', $this->array([
				$this->row($this->col($bloc('frise', 'index')))->style('row-default')
			]));
		}

		foreach (['forum/*', 'news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row($this->col($bloc('breadcrumb', 'index')))->style('row-default'),
				$this->row($this->col($bloc('module', 'index')))->style('row-default')
			]));
		}

		// À côté : l'espace membre (adhérer, se connecter), les documents, les partenaires ; à côté du forum, ses
		// chiffres et son activité ; rien à côté de l'espace membre, qui a son propre menu.
		$cote = [$bloc('user', 'index', 'panel-color')];

		if ($present('downloads'))
		{
			$cote[] = $bloc('downloads', 'popular', 'panel-default');
		}

		if ($present('partners'))
		{
			$cote[] = $bloc('partners', 'column', 'panel-default');
		}

		$dispositions->set('*', 'À côté', $this->array([
			$this->row($this->col(...$cote))->style('row-default')
		]));

		if ($present('forum'))
		{
			$dispositions->set('forum/*', 'À côté', $this->array([
				$this->row($this->col(
						$bloc('forum', 'statistics', 'panel-default'),
						$bloc('forum', 'activity', 'panel-default')
					))
					->style('row-default')
			]));
		}

		$dispositions->set('user/*', 'À côté', $this->array([]));

		// Le pied : quelques chemins, comme la table d'un livre.
		$chemins = [];

		foreach ([
			[$this->lang('Agenda'),    'calendar'],
			[$this->lang('Documents'), 'downloads'],
			[$this->lang('Contact'),   'contact']
		] as [$titre, $url])
		{
			$chemins[] = [
				'title' => utf8_htmlentities($titre),
				'url'   => $url
			];
		}

		$dispositions->set('*', 'Pied de page', $this->array([
			$this->row($this->col($bloc('navigation', 'index', NULL, ['links' => $chemins, 'panel' => 0])))->style('row-default')
		]));

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->chronique_background)->delete();
		NeoFrag()->model2('file', $this->config->chronique_header)->delete();
		NeoFrag()->model2('file', $this->config->chronique_logo)->delete();

		foreach ([
			'chronique_background', 'chronique_background_repeat', 'chronique_background_attachment',
			'chronique_background_position', 'chronique_background_color',
			'chronique_header', 'chronique_header_repeat', 'chronique_header_attachment', 'chronique_header_position',
			'chronique_logo', 'chronique_theme_color', 'chronique_text_color', 'chronique_appel'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
