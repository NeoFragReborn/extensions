<?php
/**
 * https://neofr.ag
 * Forge — thème gaming « fonte en fusion ». Rouge lave, nuit par défaut
 * (+ mode jour), titres Rajdhani, signature lueur de braise. LGPLv3.
 */

namespace NF\Themes\Forge;

use NF\NeoFrag\Addons\Theme;

class Forge extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Forge',
			'description' => $this->lang('Thème « fonte en fusion » : rouge lave sur charbon, lueur de braise, titres Rajdhani, mode jour au choix du visiteur ; couleurs, images et logo réglables.'),
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
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
				->config('forge_background_color',      '#16100e')
				->config('forge_header',                0,             'int')
				->config('forge_header_repeat',         'no-repeat')
				->config('forge_header_attachment',     'scroll')
				->config('forge_header_position',       'center top')
				->config('forge_header_color',          '#e2502b')
				->config('forge_logo',                  0,             'int')
				->config('forge_theme_color',           '#e2502b')
				->config('forge_text_color',            '#e8d8d0')
				->config('forge_navbar_display',        FALSE,         'bool');

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
								'color_description' => '#f3d9cf'
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
		NeoFrag()->model2('file', $this->config->forge_background)->delete();
		NeoFrag()->model2('file', $this->config->forge_header)->delete();
		NeoFrag()->model2('file', $this->config->forge_logo)->delete();

		foreach ([
			'forge_background', 'forge_background_repeat', 'forge_background_attachment',
			'forge_background_position', 'forge_background_color',
			'forge_header', 'forge_header_repeat', 'forge_header_attachment',
			'forge_header_position', 'forge_header_color',
			'forge_logo', 'forge_theme_color', 'forge_text_color', 'forge_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
