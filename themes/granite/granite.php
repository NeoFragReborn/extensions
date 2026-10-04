<?php
/**
 * https://neofr.ag
 * Granite — thème gaming sobre « roche & sommet ». Direction artistique
 * teal/ardoise modernisée, clin d'œil aux templates clan d'antan. Mode jour
 * par défaut + mode nuit. LGPLv3.
 */

namespace NF\Themes\Granite;

use NF\NeoFrag\Addons\Theme;

class Granite extends Theme
{
	protected function __info()
	{
		return [
			'title'       => 'Granite',
			'description' => $this->lang('Thème sobre « roche et sommet » : bleu canard sur ardoise, titres condensés Oswald, mode nuit au choix du visiteur ; couleurs, images et logo réglables.'),
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
				->config('granite_background_color',      '#f3f5f7')
				->config('granite_header',                0,             'int')
				->config('granite_header_repeat',         'no-repeat')
				->config('granite_header_attachment',     'scroll')
				->config('granite_header_position',       'center top')
				->config('granite_header_color',          '#0e7c86')
				->config('granite_logo',                  0,             'int')
				->config('granite_theme_color',           '#0e7c86')
				->config('granite_text_color',            '#1f2933')
				->config('granite_navbar_display',        FALSE,         'bool');

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
								'color_description' => '#d6e4e6'
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
		NeoFrag()->model2('file', $this->config->granite_background)->delete();
		NeoFrag()->model2('file', $this->config->granite_header)->delete();
		NeoFrag()->model2('file', $this->config->granite_logo)->delete();

		foreach ([
			'granite_background', 'granite_background_repeat', 'granite_background_attachment',
			'granite_background_position', 'granite_background_color',
			'granite_header', 'granite_header_repeat', 'granite_header_attachment',
			'granite_header_position', 'granite_header_color',
			'granite_logo', 'granite_theme_color', 'granite_text_color', 'granite_navbar_display'
		] as $key)
		{
			$this->config->unset($key);
		}

		return parent::uninstall($remove);
	}
}
