<?php
/**
 * https://neofr.ag
 * Blockcraft — thème orienté serveurs de jeu type bac à sable (blocs/biomes).
 * Direction artistique originale inspirée des univers cubiques. Mode jour (biome)
 * par défaut + mode nuit (grotte). Released under CC BY-NC-SA 4.0.
 */

namespace NF\Themes\Blockcraft;

use NF\NeoFrag\Addons\Theme;

class Blockcraft extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Blockcraft',
			'description' => $this->lang('Thème gaming inspiré des univers cubiques. Mode jour « biome » et mode nuit « grotte », accent vert herbe, entièrement personnalisable.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag fork',
			'license'     => 'Creative Commons CC BY-NC-SA 4.0',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0.0',
			'depends' => [
				'neofrag' => '0.2.1'
			],
			'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
			'regions'     => [
				'header'         => 'Header',
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
				->config('blockcraft_background_color',      '#eef3f7')
				->config('blockcraft_header',                0,             'int')
				->config('blockcraft_header_repeat',         'no-repeat')
				->config('blockcraft_header_attachment',     'scroll')
				->config('blockcraft_header_position',       'center top')
				->config('blockcraft_header_color',          '#6aa84f')
				->config('blockcraft_logo',                  0,             'int')
				->config('blockcraft_theme_color',           '#6aa84f')
				->config('blockcraft_text_color',            '#2e2a25')
				->config('blockcraft_navbar_display',        FALSE,         'bool');

		$dispositions = $this->array();

		$dispositions->set('*', 'Header', $this->array([
			$this->row(
					$this->col(
						$this->widget($this->db->insert('nf_widgets', [
							'widget'   => 'header',
							'type'     => 'index',
							'settings' => serialize([
								'display'           => 'logo',
								'align'             => 'text-start',
								'title'             => '',
								'description'       => '',
								'color_title'       => '#ffffff',
								'color_description' => '#e9efe4'
							])
						]))
					)
				)
				->style('row-default'),
			$this->row(
					$this->col(
						$this	->widget($this->db->insert('nf_widgets', [
									'widget'   => 'navigation',
									'type'     => 'index',
									'settings' => serialize([
										'links'   => [
											[
												'title' => utf8_htmlentities($this->lang('Accueil')),
												'url'   => ''
											],
											[
												'title' => utf8_htmlentities($this->lang('Actualités')),
												'url'   => 'news'
											],
											[
												'title' => utf8_htmlentities($this->lang('Forum')),
												'url'   => 'forum'
											],
											[
												'title' => utf8_htmlentities($this->lang('Équipes')),
												'url'   => 'teams'
											],
											[
												'title' => utf8_htmlentities($this->lang('Galerie')),
												'url'   => 'gallery'
											],
											[
												'title' => utf8_htmlentities($this->lang('Membres')),
												'url'   => 'members'
											],
											[
												'title' => utf8_htmlentities($this->lang('Contact')),
												'url'   => 'contact'
											]
										]
									])
								]))
					)
				)
				->style('row-dark')
		]));

		$dispositions->set('/', 'Avant-contenu', $this->array([
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

		$dispositions->set('*', 'Contenu', $this->array([
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

		return parent::install($dispositions);
	}

	public function uninstall($remove = TRUE)
	{
		NeoFrag()->model2('file', $this->config->blockcraft_background)->delete();
		NeoFrag()->model2('file', $this->config->blockcraft_header)->delete();
		NeoFrag()->model2('file', $this->config->blockcraft_logo)->delete();

		foreach ([
			'blockcraft_background', 'blockcraft_background_repeat', 'blockcraft_background_attachment',
			'blockcraft_background_position', 'blockcraft_background_color',
			'blockcraft_header', 'blockcraft_header_repeat', 'blockcraft_header_attachment',
			'blockcraft_header_position', 'blockcraft_header_color',
			'blockcraft_logo', 'blockcraft_theme_color', 'blockcraft_text_color', 'blockcraft_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
