<?php
/**
 * https://neofr.ag
 * Forge — panneau d'administration du thème. Depuis la 2.0.0 (« Coulée ») : l'image du foyer (le haut de l'accueil),
 * le logo du rail, l'arrière-plan, les couleurs, les braises du foyer et les réseaux sociaux.
 */

namespace NF\Themes\Forge\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index()
	{
		$this->js('admin');

		$image_check = function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
			{
				return $this->lang('Please choose an image file');
			}
		};

		/* ---------------------------------------------------------------- Header */

		$form_header = $this->form()
			->add_rules([
				'header' => [
					'label'       => $this->lang('Image du foyer'),
					'value'       => $this->config->forge_header,
					'type'        => 'file',
					'upload'      => 'themes/forge/headers',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Laisser vide pour utiliser le dégradé du thème.')
				],
				'repeat' => [
					'label'  => $this->lang('Répétition'),
					'value'  => $this->config->forge_header_repeat,
					'values' => [
						'no-repeat' => $this->lang('Non'),
						'repeat-x'  => $this->lang('Horizontalement'),
						'repeat-y'  => $this->lang('Verticalement'),
						'repeat'    => $this->lang('Les deux')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionX' => [
					'label'  => $this->lang('Position'),
					'value'  => explode(' ', (string) $this->config->forge_header_position ?: 'center center')[0],
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionY' => [
					'value'  => explode(' ', (string) $this->config->forge_header_position ?: 'center center')[1] ?? 'center',
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'fixed' => [
					'checked' => ['on' => $this->config->forge_header_attachment == 'fixed'],
					'values'  => ['on' => $this->lang('Image fixe (parallax)')],
					'type'    => 'checkbox'
				],
				'color' => [
					'label' => $this->lang('Couleur de fond'),
					'value' => $this->config->forge_header_color,
					'type'  => 'colorpicker',
					'rules' => 'required',
					'size'  => 'col-3'
				],
				'logo' => [
					'label'       => $this->lang('Logo du site'),
					'value'       => $this->config->forge_logo,
					'type'        => 'file',
					'upload'      => 'themes/forge/logos',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Le blason du rail. Laissé vide : le logo du site (réglages généraux), sinon les initiales de son nom.')
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* ----------------------------------------------------------- Background */

		$form_background = $this->form()
			->add_rules([
				'background' => [
					'label'       => $this->lang('Image de fond'),
					'value'       => $this->config->forge_background,
					'type'        => 'file',
					'upload'      => 'themes/forge/backgrounds',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Laisser vide pour utiliser la couleur unie du thème.')
				],
				'repeat' => [
					'label'  => $this->lang('Répétition'),
					'value'  => $this->config->forge_background_repeat,
					'values' => [
						'no-repeat' => $this->lang('Non'),
						'repeat-x'  => $this->lang('Horizontalement'),
						'repeat-y'  => $this->lang('Verticalement'),
						'repeat'    => $this->lang('Les deux')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionX' => [
					'label'  => $this->lang('Position'),
					'value'  => explode(' ', (string) $this->config->forge_background_position ?: 'center top')[0],
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionY' => [
					'value'  => explode(' ', (string) $this->config->forge_background_position ?: 'center top')[1] ?? 'top',
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'fixed' => [
					'checked' => ['on' => $this->config->forge_background_attachment == 'fixed'],
					'values'  => ['on' => $this->lang('Image fixe (parallax)')],
					'type'    => 'checkbox'
				],
				'color' => [
					'label' => $this->lang('Couleur de fond'),
					'value' => $this->config->forge_background_color,
					'type'  => 'colorpicker',
					'rules' => 'required',
					'size'  => 'col-3'
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* ------------------------------------------------------------- Settings */

		$form_settings = $this->form()
			->add_rules([
				'theme_color' => [
					'label'       => $this->lang('Couleur d\'accent'),
					'value'       => $this->config->forge_theme_color,
					'type'        => 'colorpicker',
					'description' => $this->lang('Couleur principale du thème (boutons, liens, accents)'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				'text_color' => [
					'label'       => $this->lang('Couleur du texte'),
					'value'       => $this->config->forge_text_color,
					'type'        => 'colorpicker',
					'description' => $this->lang('Couleur appliquée au texte principal'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				// Les braises qui montent dans le foyer (le haut de l'accueil).
				'braises' => [
					'label'  => $this->lang('Braises du foyer'),
					// Jamais enregistré, un réglage se lit FALSE : les braises « douces » par défaut.
					'value'  => $this->config->forge_braises === FALSE ? '1' : (string) max(0, min(2, (int) $this->config->forge_braises)),
					'values' => [
						'0' => $this->lang('Aucune'),
						'1' => $this->lang('Douces'),
						'2' => $this->lang('Vives')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* -------------------------------------------------------------- Socials */

		$socials_def = [
			'facebook'   => ['Facebook',    'fab fa-facebook-f'],
			'twitter'    => ['Twitter / X', 'fab fa-twitter'],
			'youtube'    => ['Youtube',     'fab fa-youtube'],
			'twitch'     => ['Twitch',      'fab fa-twitch'],
			'discord'    => ['Discord',     'fab fa-discord'],
			'github'     => ['GitHub',      'fab fa-github'],
			'instagram'  => ['Instagram',   'fab fa-instagram'],
			'tiktok'     => ['TikTok',      'fab fa-tiktok']
		];

		$social_rules = [];
		foreach ($socials_def as $key => $meta)
		{
			list($label, $icon) = $meta;
			$social_rules[$key] = [
				'label'       => $label,
				'icon'        => $icon,
				'value'       => $this->config->{'nf_social_'.$key},
				'type'        => 'text',
				'description' => $this->lang('Indiquez l\'URL complète')
			];
		}

		$form_socials = $this->form()
			->add_rules($social_rules)
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* --------------------------------------------------------------- Submit */

		if ($form_header->is_valid($post))
		{
			if ($post['header']) $this->config('forge_header', $post['header'], 'int');
			else                 $this->config->unset('forge_header');

			if ($post['logo'])   $this->config('forge_logo', $post['logo'], 'int');
			else                 $this->config->unset('forge_logo');

			$this	->config('forge_header_repeat',     $post['repeat'])
					->config('forge_header_attachment', in_array('on', $post['fixed']) ? 'fixed' : 'scroll')
					->config('forge_header_position',   $post['positionX'].' '.$post['positionY'])
					->config('forge_header_color',      $post['color'])
					->config('nf_version_css',          time());

			notify($this->lang('Foyer mis à jour !'));
			redirect($this->url->location.'#header');
		}
		else if ($form_background->is_valid($post))
		{
			if ($post['background']) $this->config('forge_background', $post['background'], 'int');
			else                     $this->config->unset('forge_background');

			$this	->config('forge_background_repeat',     $post['repeat'])
					->config('forge_background_attachment', in_array('on', $post['fixed']) ? 'fixed' : 'scroll')
					->config('forge_background_position',   $post['positionX'].' '.$post['positionY'])
					->config('forge_background_color',      $post['color'])
					->config('nf_version_css',              time());

			notify($this->lang('Arrière-plan mis à jour !'));
			redirect($this->url->location.'#background');
		}
		else if ($form_settings->is_valid($post))
		{
			$this	->config('forge_theme_color',    $post['theme_color'])
					->config('forge_text_color',     $post['text_color'])
					->config('forge_braises',        max(0, min(2, (int) $post['braises'])), 'int')
					->config('nf_version_css',       time());

			notify($this->lang('Configuration mise à jour !'));
			redirect($this->url->location.'#settings');
		}
		else if ($form_socials->is_valid($post))
		{
			foreach (array_keys($socials_def) as $key)
			{
				$this->config('nf_social_'.$key, $post[$key]);
			}

			notify($this->lang('Réseaux sociaux mis à jour !'));
			redirect($this->url->location.'#socials');
		}

		return $this->row(
			$this	->col(
						$this	->panel()
								->body($this->view('admin/menu', [
									'theme_name' => $this->__caller->info()->name
								]), FALSE)
					)
					->size('col-12 col-md-4 col-lg-3'),
			$this	->col(
						$this	->panel()
								->heading($this->__caller->info()->title, 'fas fa-paint-brush')
								->body($this->view('admin/index', [
									'theme'           => $this->__caller,
									'form_header'     => $form_header->display(),
									'form_background' => $form_background->display(),
									'form_settings'   => $form_settings->display(),
									'form_socials'    => $form_socials->display()
								]))
					)
					->size('col-12 col-md-8 col-lg-9')
		);
	}
}
