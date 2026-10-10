<?php
/**
 * https://neofr.ag
 * Granite 2.0 « Gazette » — le journal de pierre, pour les associations, les clubs et les sites d'actualité
 * (chantier B, étape B2 ; maquette A retenue le 2026-10-06). Le site comme le journal du club : la date et le titre
 * imprimés en tête, les rubriques entre deux filets, une ligne « En bref » qui défile, une une à colonnes avec ses
 * capitales ornées, un pied en « ours ». Clair par défaut, nuit (« à l'encre ») au choix du visiteur. LGPLv3.
 *
 * couplage(forum): la ligne « En bref » ne porte les derniers sujets du forum que si le module Forum est installé.
 * couplage(calendar): l'agenda de la colonne, de même : seulement si le module Calendrier est installé.
 * couplage(surveys): le sondage de la colonne, de même : seulement si le module Sondages est installé.
 * couplage(partners): les partenaires du pied de page, de même : seulement si le module Partenaires est installé.
 */

namespace NF\Themes\Granite;

use NF\NeoFrag\Addons\Theme;

class Granite extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Granite',
			'description' => $this->lang('Thème « Gazette », le journal de pierre des associations et des clubs : la date et le titre imprimés en tête, les rubriques entre deux filets, une ligne « En bref » qui défile, une une à colonnes avec ses capitales ornées, un pied en « ours » ; titres Playfair Display, texte Source Serif, nuit « à l’encre » au choix du visiteur ; couleurs, image du titre, logo et lettrines réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '2.0.0',
			// Le cœur 1.2.35 : son pont donne aux barres des sondages la couleur du thème (le bleu de Bootstrap avant).
			'depends' => [
				'neofrag' => '1.2.35'
			],
			// Les zones se lisent par leur RANG (0 à 4) ; leurs noms se traduisent (langs/*.php).
			'zones'       => ['Rubriques', 'En bref', 'Contenu', 'Après le contenu', 'Pied de page'],
			'regions'     => [
				'rubriques'     => 'Rubriques',
				'breves'        => 'En bref',
				'content'       => 'Contenu',
				'after_content' => 'Après le contenu',
				'footer'        => 'Pied de page',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
				->css('fonts/playfair-display') // les polices, servies par le site (tools/polices-locales.php)
				->css('fonts/source-serif-4')
				->css('fonts/jetbrains-mono')
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
				->js('granite');
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
		$this	->config('granite_background',            0,             'int')
				->config('granite_background_repeat',     'repeat')
				->config('granite_background_attachment', 'scroll')
				->config('granite_background_position',   'center top')
				->config('granite_background_color',      '#f4efe4')
				->config('granite_header',                0,             'int')
				->config('granite_header_repeat',         'no-repeat')
				->config('granite_header_attachment',     'scroll')
				->config('granite_header_position',       'center center')
				->config('granite_header_color',          '#f4efe4')
				->config('granite_logo',                  0,             'int')
				->config('granite_theme_color',           '#9b2c1f')
				->config('granite_text_color',            '#1e1c19')
				// Les capitales ornées des articles : 1 oui, 0 non. En ENTIER : un réglage jamais enregistré se lit
				// FALSE, comme un booléen éteint.
				->config('granite_lettrines',             1,             'int');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait sa case vide et sa ligne de nf_widgets orpheline : la ligne
		// « En bref », l'agenda, le sondage et les partenaires ne se posent que si leur module est là.
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

		// Les rubriques : la navigation, en ligne, entre deux filets.
		$liens = [];

		foreach ([
			[$this->lang('À la une'),   ''],
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

		$dispositions->set('*', 'Rubriques', $this->array([
			$this->row($this->col($bloc('navigation', 'index', NULL, ['links' => $liens])))->style('row-default')
		]));

		// « En bref » sur la une : les derniers sujets du forum, qui défilent.
		if ($present('forum'))
		{
			$dispositions->set('/', 'En bref', $this->array([
				$this->row($this->col($bloc('forum', 'topics')))->style('row-default')
			]));
		}

		// La une : le module à gauche (la première actualité en grand) ; l'agenda, le sondage et qui est en ligne
		// dans la colonne de droite.
		$colonne = [];

		if ($present('calendar'))
		{
			$colonne[] = $bloc('calendar', 'upcoming', 'panel-default');
		}

		if ($present('surveys'))
		{
			$colonne[] = $bloc('surveys', 'current', 'panel-default');
		}

		$colonne[] = $bloc('members', 'online', 'panel-default');

		$dispositions->set('*', 'Contenu', $this->array([
			$this->row(
					$this->col($bloc('module', 'index'))->size('col-lg-8'),
					$this->col(...$colonne)->size('col-lg-4')
				)
				->style('row-default')
		]));

		foreach (['forum/*', 'news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row($this->col($bloc('breadcrumb', 'index')))->style('row-default'),
				$this->row($this->col($bloc('module', 'index')))->style('row-default')
			]));
		}

		$dispositions->set('forum/*', 'Après le contenu', $this->array([
			$this->row(
					$this->col($bloc('forum', 'statistics', 'panel-default'))->size('col-md-4'),
					$this->col($bloc('forum', 'activity', 'panel-default'))->size('col-md-8')
				)
				->style('row-default')
		]));

		// Le pied, en « ours » : les partenaires (« Avec le soutien de »).
		if ($present('partners'))
		{
			$dispositions->set('*', 'Pied de page', $this->array([
				$this->row($this->col($bloc('partners', 'column')))->style('row-default')
			]));
		}

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->granite_background)->delete();
		NeoFrag()->model2('file', $this->config->granite_header)->delete();
		NeoFrag()->model2('file', $this->config->granite_logo)->delete();

		foreach ([
			'granite_background', 'granite_background_repeat', 'granite_background_attachment',
			'granite_background_position', 'granite_background_color',
			'granite_header', 'granite_header_repeat', 'granite_header_attachment',
			'granite_header_position', 'granite_header_color',
			'granite_logo', 'granite_theme_color', 'granite_text_color', 'granite_lettrines',
			// Le réglage de la barre du haut fixe, retiré avec la 2.0.0.
			'granite_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
