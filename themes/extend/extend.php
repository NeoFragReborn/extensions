<?php
/**
 * https://neofr.ag
 * Extend — thème navy & bleu acier, élégant et personnalisable. Titres condensés
 * Economica, bascule nuit/jour, mise en page riche (navigation, bannière, multi-zones).
 * Port BS5 du thème « Extend » de Chewbaka (CC BY-NC-SA 4.0).
 */

namespace NF\Themes\Extend;

use NF\NeoFrag\Addons\Theme;

class Extend extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Extend',
			'description' => $this->lang('Thème « Extend » : navy et bleu acier, titres condensés Economica, bascule nuit/jour, mise en page riche (navigation, bannière, multi-zones). Entièrement personnalisable.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Chewbaka — portage NeoFrag Reborn',
			'license'     => 'Creative Commons CC BY-NC-SA 4.0 <https://creativecommons.org/licenses/by-nc-sa/4.0/>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0.0',
			'depends' => [
				'neofrag' => '0.2.1'
			],
			'zones'       => ['Navigation', 'Bannière', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
			'regions'     => [
				'navigation'     => 'Navigation',
				'banner'         => 'Bannière',
				'before_content' => 'Avant-contenu',
				'content'        => 'Contenu',
				'after_content'  => 'Post-contenu',
				'footer'         => 'Footer',
			]
		];
	}

	public function __init()
	{
		$this	->css('bootstrap.min')->css('nf-bs5-bridge')
				->css('icons/fontawesome.min')
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
				->config('extend_background_color',      '#11171a')
				->config('extend_header',                0,             'int')
				->config('extend_header_repeat',         'no-repeat')
				->config('extend_header_attachment',     'scroll')
				->config('extend_header_position',       'center top')
				->config('extend_header_color',          '#236daf')
				->config('extend_logo',                  0,             'int')
				->config('extend_theme_color',           '#236daf')
				->config('extend_text_color',            '#c3cdd6')
				->config('extend_navbar_display',        FALSE,         'bool');

		$dispositions = $this->array();

		/* ------------------------------------------------------------ Navigation */

		$dispositions->set('*', 'Navigation', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget'   => 'navigation',
									'type'     => 'index',
									'settings' => serialize([
										'links'   => [
											['title' => utf8_htmlentities($this->lang('Accueil')),    'url' => ''],
											['title' => utf8_htmlentities($this->lang('Actualités')),  'url' => 'news'],
											['title' => utf8_htmlentities($this->lang('Forum')),       'url' => 'forum'],
											['title' => utf8_htmlentities($this->lang('Équipes')),     'url' => 'teams'],
											['title' => utf8_htmlentities($this->lang('Galerie')),     'url' => 'gallery'],
											['title' => utf8_htmlentities($this->lang('Membres')),     'url' => 'members'],
											['title' => utf8_htmlentities($this->lang('Contact')),     'url' => 'contact']
										]
									])
								]))
					)
				)
				->style('row-dark')
		]));

		/* -------------------------------------------------------------- Bannière */

		$dispositions->set('/', 'Bannière', $this->array([
			$this->row(
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget' => 'slider',
							'type'   => 'index'
						]))
					)
				)
				->style('row-default')
		]));

		/* --------------------------------------------------------- Avant-contenu */

		$dispositions->set('/', 'Avant-contenu', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'events',
									'type'   => 'matches'
								]))
								->style('panel-default')
					)
					->size('col-md-4'),
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'news',
									'type'   => 'index'
								]))
								->style('panel-default')
					)
					->size('col-md-4'),
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'events',
									'type'   => 'upcoming'
								]))
								->style('panel-default')
					)
					->size('col-md-4')
				)
				->style('row-default')
		]));

		/* ---------------------------------------------------------------- Contenu */

		$dispositions->set('*', 'Contenu', $this->array([
			$this->row(
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget' => 'breadcrumb',
							'type'   => 'index'
						]))
					)
					->size('col-md-8'),
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget' => 'search',
							'type'   => 'index'
						]))
					)
					->size('col-md-4')
				)
				->style('row-default'),
			$this->row(
					$this->col(
							$this->widget($this->db->insert('nf_widgets', [
								'widget' => 'module',
								'type'   => 'index'
							]))
						)
						->size('col-md-8'),
					$this->col(
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'user',
										'type'   => 'index'
									]))
									->style('panel-color'),
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'members',
										'type'   => 'online'
									]))
									->style('panel-default'),
							$this	->widget($this->db->insert('nf_widgets', [
										'widget' => 'news',
										'type'   => 'categories'
									]))
									->style('panel-default'),
							$this	->widget($this->db->insert('nf_widgets', [
										'widget'   => 'talks',
										'type'     => 'index',
										'settings' => serialize([
											'talk_id' => 2
										])
									]))
									->style('panel-header')
						)
						->size('col-md-4')
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

		/* ----------------------------------------------------------- Post-contenu */

		$dispositions->set('*', 'Post-contenu', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'partners',
									'type'   => 'index'
								]))
								->style('panel-default')
					)
				)
				->style('row-default')
		]));

		$dispositions->set('forum/*', 'Post-contenu', $this->array([
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'statistics'
								]))
								->style('panel-header')
					)
					->size('col-md-4'),
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget' => 'forum',
									'type'   => 'activity'
								]))
								->style('panel-header')
					)
					->size('col-md-8')
				)
				->style('row-default')
		]));

		/* ---------------------------------------------------------------- Footer */

		/* ----------------------------------------------------------------- Footer */

		// La zone de pied est laissée VIDE, et c'est délibéré. Elle contenait un widget HTML
		// « Propulsé par NeoFrag Reborn » — exactement ce que le gabarit du thème écrit déjà de son
		// côté, deux lignes plus bas (`ex-copy`). Un site neuf en thème Extend affichait donc la
		// mention DEUX FOIS, une fois dans un panneau encadré et une fois dans la barre de pied.
		//
		// Aucun des trois autres thèmes (forge, blockcraft, granite) ne pose de widget là : ils
		// déclarent la région pour que l'administrateur puisse y mettre ce qu'il veut, et s'en
		// tiennent à leur propre ligne de copyright. Extend s'aligne.

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
			'extend_header', 'extend_header_repeat', 'extend_header_attachment',
			'extend_header_position', 'extend_header_color',
			'extend_logo', 'extend_theme_color', 'extend_text_color', 'extend_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
