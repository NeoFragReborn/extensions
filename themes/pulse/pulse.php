<?php
/**
 * https://neofr.ag
 * Pulse — la maison commune, pour les associations, les clubs et les communautés (chantier B, étape B3 ; maquette
 * « Dallage » du 2026-10-06, devenue un thème à part entière). Une barre claire en haut, l'appel « Adhérer » toujours en
 * vue ; l'accueil se pose en mosaïque de dalles de tailles différentes — la grande dalle d'accueil, puis chaque bloc :
 * le prochain rendez-vous, le site en chiffres, les actualités, l'agenda, le sondage, les photos… ; les autres pages
 * en dalles aux coins très arrondis, le forum en cartes ; un pied sombre où bat le point du plan. Titres Bricolage
 * Grotesque, texte Manrope ; jour « clair », nuit « ardoise ». LGPLv3.
 *
 * couplage(calendar): le prochain rendez-vous et l'agenda de la mosaïque ne se posent que si le module Calendrier est installé.
 * couplage(news): les actualités de la mosaïque, de même : seulement si le module Actualités est installé.
 * couplage(surveys): le sondage, de même : seulement si le module Sondages est installé.
 * couplage(downloads): les documents, de même : seulement si le module Téléchargements est installé.
 * couplage(forum): les dernières discussions de la mosaïque, et les chiffres et l'activité du forum à côté de ses pages, de même : seulement si le module Forum est installé.
 * couplage(gallery): les dernières photos, de même : seulement si le module Galerie est installé.
 * couplage(partners): les partenaires, de même : seulement si le module Partenaires est installé.
 */

namespace NF\Themes\Pulse;

use NF\NeoFrag\Addons\Theme;

class Pulse extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Pulse',
			'description' => $this->lang('Thème « Pulse », la maison commune des associations, des clubs et des communautés : une barre claire et l’appel « Adhérer » toujours en vue, un accueil en mosaïque de dalles (le prochain rendez-vous, le site en chiffres, les actualités, l’agenda, le sondage, les photos…), le forum en cartes, un pied sombre où bat le point du plan ; titres Bricolage Grotesque, texte Manrope, nuit « ardoise » au choix du visiteur ; couleurs, image d’accueil, logo et appel à adhérer réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0.0',
			// Le cœur 1.2.38 : le prochain rendez-vous du calendrier, le site en chiffres et les dernières photos.
			'depends' => [
				'neofrag' => '1.2.38'
			],
			// Les zones se lisent par leur RANG (0 à 4) ; leurs noms se traduisent (langs/*.php).
			'zones'       => ['Barre de navigation', 'Mosaïque', 'Contenu', 'À côté', 'Pied de page'],
			'regions'     => [
				'navigation' => 'Barre de navigation',
				'mosaique'   => 'Mosaïque',
				'content'    => 'Contenu',
				'cote'       => 'À côté',
				'footer'     => 'Pied de page',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
				->css('fonts/bricolage-grotesque') // les polices, servies par le site (tools/polices-locales.php)
				->css('fonts/manrope')
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
				->js('pulse');
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
		$this	->config('pulse_background',            0,             'int')
				->config('pulse_background_repeat',     'repeat')
				->config('pulse_background_attachment', 'scroll')
				->config('pulse_background_position',   'center top')
				->config('pulse_background_color',      '#e8e6e1')
				->config('pulse_accueil',               0,             'int')
				->config('pulse_logo',                  0,             'int')
				->config('pulse_theme_color',           '#3d5566')
				->config('pulse_text_color',            '#23282b')
				// L'appel à adhérer, dans la barre : son adresse (vide : l'inscription du site, si elle est ouverte).
				->config('pulse_appel',                 '');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait sa dalle vide et sa ligne de nf_widgets orpheline : chaque dalle
		// ne se pose que si son module est là ; les trois affichages neufs de la 1.2.38, que si leur widget l'est.
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

		// La barre : les rubriques du site, en pastilles.
		$dispositions->set('*', 'Barre de navigation', $this->array([
			$this->row($this->col($bloc('navigation', 'index', NULL, ['links' => $liens([
				[$this->lang('Accueil'),    ''],
				[$this->lang('Actualités'), 'news'],
				[$this->lang('Agenda'),     'calendar'],
				[$this->lang('Forum'),      'forum'],
				[$this->lang('Galerie'),    'gallery'],
				[$this->lang('Documents'),  'downloads'],
				[$this->lang('Contact'),    'contact']
			]), 'panel' => 0])))->style('row-default')
		]));

		// La mosaïque de l'accueil, après la grande dalle d'accueil que le thème pose lui-même (six colonnes sur douze,
		// deux rangs) : chaque colonne est une dalle, aussi large que sa taille (col-lg-N : N douzièmes).
		$mosaique = [];

		$tete = [];

		if ($present('calendar'))
		{
			$tete[] = $this->col($bloc('calendar', 'prochain', 'panel-color'))->size('col-lg-6');
		}

		if ($widget_present('chiffres'))
		{
			$tete[] = $this->col($bloc('chiffres', 'index', 'panel-default'))->size('col-lg-6');
		}

		if ($tete)
		{
			$mosaique[] = $this->row(...$tete)->style('row-default');
		}

		$milieu = [];

		if ($present('news'))
		{
			$milieu[] = $this->col($bloc('news', 'index', 'panel-default'))->size($present('calendar') ? 'col-lg-8' : 'col-lg-12');
		}

		if ($present('calendar'))
		{
			$milieu[] = $this->col($bloc('calendar', 'upcoming', 'panel-default'))->size($present('news') ? 'col-lg-4' : 'col-lg-12');
		}

		if ($milieu)
		{
			$mosaique[] = $this->row(...$milieu)->style('row-default');
		}

		$bas = [];

		if ($present('surveys'))
		{
			$bas[] = $this->col($bloc('surveys', 'current', 'panel-header'))->size('col-lg-4');
		}

		if ($present('forum'))
		{
			$bas[] = $this->col($bloc('forum', 'topics', 'panel-default'))->size('col-lg-4');
		}

		if ($present('downloads'))
		{
			$bas[] = $this->col($bloc('downloads', 'popular', 'panel-default'))->size('col-lg-4');
		}

		if ($bas)
		{
			$mosaique[] = $this->row(...$bas)->style('row-default');
		}

		$fin = [];

		if ($present('gallery'))
		{
			$fin[] = $this->col($bloc('gallery', 'grille', 'panel-default'))->size($present('partners') ? 'col-lg-7' : 'col-lg-12');
		}

		if ($present('partners'))
		{
			$fin[] = $this->col($bloc('partners', 'column', 'panel-default'))->size($present('gallery') ? 'col-lg-5' : 'col-lg-12');
		}

		if ($fin)
		{
			$mosaique[] = $this->row(...$fin)->style('row-default');
		}

		$dispositions->set('/', 'Mosaïque', $this->array($mosaique));

		// Le contenu : le module partout, sauf sur l'accueil, que la mosaïque porte ; le fil d'Ariane là où l'on
		// descend dans les pages.
		$dispositions->set('*', 'Contenu', $this->array([
			$this->row($this->col($bloc('module', 'index')))->style('row-default')
		]));

		$dispositions->set('/', 'Contenu', $this->array([]));

		foreach (['forum/*', 'news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row($this->col($bloc('breadcrumb', 'index')))->style('row-default'),
				$this->row($this->col($bloc('module', 'index')))->style('row-default')
			]));
		}

		// À côté : rien en général — les pages prennent toute la largeur, en dalles ; à côté du forum, ses chiffres et
		// son activité.
		$dispositions->set('*', 'À côté', $this->array([]));

		if ($present('forum'))
		{
			$dispositions->set('forum/*', 'À côté', $this->array([
				$this->row($this->col(
						$bloc('forum', 'statistics', 'panel-color'),
						$bloc('forum', 'activity', 'panel-default')
					))
					->style('row-default')
			]));
		}

		// Le pied : quelques chemins.
		$dispositions->set('*', 'Pied de page', $this->array([
			$this->row($this->col($bloc('navigation', 'vertical', NULL, ['links' => $liens([
				[$this->lang('Agenda'),    'calendar'],
				[$this->lang('Documents'), 'downloads'],
				[$this->lang('Membres'),   'members'],
				[$this->lang('Contact'),   'contact']
			]), 'panel' => 0])))->style('row-default')
		]));

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->pulse_background)->delete();
		NeoFrag()->model2('file', $this->config->pulse_accueil)->delete();
		NeoFrag()->model2('file', $this->config->pulse_logo)->delete();

		foreach ([
			'pulse_background', 'pulse_background_repeat', 'pulse_background_attachment', 'pulse_background_position',
			'pulse_background_color', 'pulse_accueil', 'pulse_logo', 'pulse_theme_color', 'pulse_text_color', 'pulse_appel'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
