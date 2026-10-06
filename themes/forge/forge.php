<?php
/**
 * https://neofr.ag
 * Forge 2.0 « Coulée » — le thème des clans compétitifs (chantier B, étape B2, maquette retenue le 2026-10-06).
 * La navigation quitte le haut de la page pour un rail d'acier sur le côté (une barre d'onglets en bas au
 * téléphone) ; l'accueil s'ouvre sur un foyer de lave où montent des braises ; les blocs sont des plaques aux
 * coins coupés qui rougeoient au survol. Nuit par défaut, jour au choix du visiteur. LGPLv3.
 *
 * couplage(events): les blocs des matchs (le foyer, le tableau de bord) ne sont posés par install() que si le module
 *   Événements est installé — sinon le diaporama prend toute la largeur du foyer.
 * couplage(awards): le palmarès du tableau de bord, de même : seulement si le module Palmarès est installé.
 * couplage(partners): les partenaires du pied de page, de même : seulement si le module Partenaires est installé.
 */

namespace NF\Themes\Forge;

use NF\NeoFrag\Addons\Theme;

class Forge extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Forge',
			'description' => $this->lang('Thème « Coulée », pour les clans compétitifs : la navigation dans un rail d’acier sur le côté (une barre d’onglets en bas au téléphone), un foyer de lave où montent des braises en haut de l’accueil, des plaques aux coins coupés qui rougeoient au survol ; titres Rajdhani, mode jour au choix du visiteur ; couleurs, images, logo et braises réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '2.0.0',
			// Le socle commun des thèmes (css/nf-socle-themes.css) est arrivé avec la 1.2.32 du cœur ; la version
			// d'une feuille de thème suit ses réglages (nf_version_asset()) depuis la 1.2.34.
			'depends' => [
				'neofrag' => '1.2.34'
			],
			// Les zones se lisent par leur RANG (0 à 4) : une disposition enregistrée garde sa place si l'on
			// renomme une zone. Leurs noms se traduisent (langs/*.php).
			'zones'       => ['Rail de navigation', 'Haut de page', 'Contenu', 'Après le contenu', 'Pied de page'],
			'regions'     => [
				'rail'          => 'Rail de navigation',
				'foyer'         => 'Haut de page',
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
				// Le socle commun des thèmes (chantier B, étape B1) : ce que les quatre thèmes clones avaient
				// d'identique. AVANT la feuille du thème, qui garde son identité et peut tout redéfinir.
				->css('nf-socle-themes')
				->css('style')
				// APRES la feuille du theme, et jamais avant : elle retablit ce que le theme
				// ecrase sans le vouloir — cadre des boutons « contour », coins des cartes.
				// Voir css/nf-apres-theme.css.
				->css('nf-apres-theme')
				->js('bootstrap.bundle.min')
				->js('modal')
				->js('notify')
				->js('confirm')
				->js('theme')
				->js('forge');
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
		$this	->config('forge_background',            0,             'int')
				->config('forge_background_repeat',     'repeat')
				->config('forge_background_attachment', 'scroll')
				->config('forge_background_position',   'center top')
				->config('forge_background_color',      '#120d0b')
				->config('forge_header',                0,             'int')
				->config('forge_header_repeat',         'no-repeat')
				->config('forge_header_attachment',     'scroll')
				->config('forge_header_position',       'center center')
				->config('forge_header_color',          '#160f0c')
				->config('forge_logo',                  0,             'int')
				->config('forge_theme_color',           '#ff5a1f')
				->config('forge_text_color',            '#eaddd5')
				// Les braises du foyer : 0 aucune, 1 douces, 2 vives. En ENTIER : un réglage jamais enregistré
				// se lit FALSE, comme un booléen éteint.
				->config('forge_braises',               1,             'int');

		$dispositions = $this->array();

		// Un widget dont le module manque laisserait sa case vide et sa ligne de nf_widgets orpheline : les blocs des
		// matchs, du palmarès et des partenaires ne se posent que si leur module est là (les `couplage(…)` en tête).
		$present = function (string $module): bool {
			return ($addon = @NeoFrag()->module($module)) && $addon->is_enabled();
		};
		$bloc = function (string $nom, string $type, ?string $style = NULL) {
			$widget = $this->widget($this->db->insert('nf_widgets', [
				'widget' => $nom,
				'type'   => $type
			]));

			return $style ? $widget->style($style) : $widget;
		};

		// Le rail : la navigation, en colonne, chaque entrée avec son pictogramme.
		$liens = [];

		foreach ([
			[$this->lang('Accueil'),        '',               'fas fa-house'],
			[$this->lang('Actualités'),     'news',           'far fa-newspaper'],
			[$this->lang('Forum'),          'forum',          'far fa-comments'],
			[$this->lang('Matchs'),         'events/matches', 'fas fa-crosshairs'],
			[$this->lang('Équipes'),        'teams',          'fas fa-users'],
			[$this->lang('Galerie'),        'gallery',        'far fa-images'],
			[$this->lang('Nous rejoindre'), 'recruits',       'fas fa-user-plus'],
			[$this->lang('Contact'),        'contact',        'far fa-envelope']
		] as [$titre, $url, $icone])
		{
			$liens[] = [
				'title' => utf8_htmlentities($titre),
				'url'   => $url,
				'icon'  => $icone
			];
		}

		$dispositions->set('*', 'Rail de navigation', $this->array([
			$this->row(
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget'   => 'navigation',
							'type'     => 'vertical',
							// En JSON, le format courant des réglages de widget (Fields\Json) ; le `serialize()` des
							// autres thèmes est l'ancien, relu seulement jusqu'au prochain enregistrement.
							'settings' => \NF\NeoFrag\Fields\Json::encode([
								'links' => $liens,
								'panel' => 0
							])
						]))
					)
				)
				->style('row-default')
		]));

		// Le foyer de l'accueil : le diaporama, et la plaque des derniers résultats à côté.
		$foyer = [$this->col($bloc('slider', 'index'))->size($present('events') ? 'col-lg-8' : 'col-12')];

		if ($present('events'))
		{
			$foyer[] = $this->col($bloc('events', 'matches', 'panel-default'))->size('col-lg-4');
		}

		$dispositions->set('/', 'Haut de page', $this->array([
			$this->row(...$foyer)->style('row-default')
		]));

		// Le tableau de bord : le module à gauche ; les matchs à venir, le palmarès et qui est en ligne à droite.
		$colonne = [];

		if ($present('events'))
		{
			$colonne[] = $bloc('events', 'upcoming', 'panel-default');
		}

		if ($present('awards'))
		{
			$colonne[] = $bloc('awards', 'index', 'panel-default');
		}

		$colonne[] = $bloc('members', 'online', 'panel-default');

		$dispositions->set('*', 'Contenu', $this->array([
			$this->row(
					$this->col($bloc('module', 'index'))->size('col-lg-8'),
					$this->col(...$colonne)->size('col-lg-4')
				)
				->style('row-default')
		]));

		foreach (['forum/*', 'news/_news/*', 'user/*'] as $page)
		{
			$dispositions->set($page, 'Contenu', $this->array([
				$this->row(
						$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'breadcrumb',
								'type'   => 'index'
							]))
						)
					)
					->style('row-default'),
				$this->row(
						$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'module',
								'type'   => 'index'
							]))
						)
					)
					->style('row-default')
			]));
		}

		$dispositions->set('forum/*', 'Après le contenu', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'statistics'
								]))
								->style('panel-default')
					)
					->size('col-md-4'),
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'activity'
								]))
								->style('panel-default')
					)
					->size('col-md-8')
				)
				->style('row-default')
		]));

		// Le pied riveté : les partenaires, en ligne.
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
		NeoFrag()->model2('file', $this->config->forge_background)->delete();
		NeoFrag()->model2('file', $this->config->forge_header)->delete();
		NeoFrag()->model2('file', $this->config->forge_logo)->delete();

		foreach ([
			'forge_background', 'forge_background_repeat', 'forge_background_attachment',
			'forge_background_position', 'forge_background_color',
			'forge_header', 'forge_header_repeat', 'forge_header_attachment',
			'forge_header_position', 'forge_header_color',
			'forge_logo', 'forge_theme_color', 'forge_text_color', 'forge_braises',
			// Le réglage de la barre du haut fixe, retiré avec la 2.0.0 (le rail est toujours là).
			'forge_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
