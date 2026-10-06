<?php
/**
 * https://neofr.ag
 * Granite — panneau d'administration du thème : le titre du journal (son image, sa couleur, le logo), l'arrière-plan,
 * les couleurs, les lettrines et les réseaux sociaux.
 */

namespace NF\Themes\Granite\Controllers;

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

		/* ------------------------------------------------------ Le titre (header) */

		$form_header = $this->form()
			->add_rules([
				'header' => [
					'label'       => $this->lang('Image du titre'),
					'value'       => $this->config->granite_header,
					'type'        => 'file',
					'upload'      => 'themes/granite/headers',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Posée derrière le nom du site, éclaircie pour qu’il reste lisible. Laisser vide : le papier seul.')
				],
				'repeat' => [
					'label'  => $this->lang('Répétition'),
					'value'  => $this->config->granite_header_repeat,
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
					'value'  => explode(' ', $this->config->granite_header_position)[0],
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionY' => [
					'value'  => explode(' ', $this->config->granite_header_position)[1],
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'fixed' => [
					'checked' => ['on' => $this->config->granite_header_attachment == 'fixed'],
					'values'  => ['on' => $this->lang('Image fixe (parallax)')],
					'type'    => 'checkbox'
				],
				'color' => [
					'label'       => $this->lang('Couleur du titre'),
					// Le défaut de Granite 1.x (#0e7c86) se lit comme celui de la 2.0.0 : la couleur du papier.
					'value'       => in_array(strtolower((string) $this->config->granite_header_color), ['', '#0e7c86'], TRUE) ? '#f4efe4' : $this->config->granite_header_color,
					'type'        => 'colorpicker',
					'description' => $this->lang('Un bandeau de couleur derrière le nom du site, comme la manchette d’un quotidien. Laisser la couleur du papier (#f4efe4) pour n’en mettre aucun.'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				'logo' => [
					'label'       => $this->lang('Logo du site'),
					'value'       => $this->config->granite_logo,
					'type'        => 'file',
					'upload'      => 'themes/granite/logos',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Imprimé au-dessus du nom du site. Laissé vide : le logo du site (réglages généraux), s’il y en a un.')
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* ----------------------------------------------------------- Background */

		$form_background = $this->form()
			->add_rules([
				'background' => [
					'label'       => $this->lang('Image de fond'),
					'value'       => $this->config->granite_background,
					'type'        => 'file',
					'upload'      => 'themes/granite/backgrounds',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Laisser vide pour utiliser la couleur unie du thème.')
				],
				'repeat' => [
					'label'  => $this->lang('Répétition'),
					'value'  => $this->config->granite_background_repeat,
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
					'value'  => explode(' ', $this->config->granite_background_position)[0],
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionY' => [
					'value'  => explode(' ', $this->config->granite_background_position)[1],
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'fixed' => [
					'checked' => ['on' => $this->config->granite_background_attachment == 'fixed'],
					'values'  => ['on' => $this->lang('Image fixe (parallax)')],
					'type'    => 'checkbox'
				],
				'color' => [
					'label' => $this->lang('Couleur de fond'),
					'value' => $this->config->granite_background_color,
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
					'value'       => $this->config->granite_theme_color,
					'type'        => 'colorpicker',
					'description' => $this->lang('Couleur principale du thème (boutons, liens, accents)'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				'text_color' => [
					'label'       => $this->lang('Couleur du texte'),
					'value'       => $this->config->granite_text_color,
					'type'        => 'colorpicker',
					'description' => $this->lang('Couleur appliquée au texte principal'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				// Les capitales ornées de la une et des articles. Jamais enregistré, le réglage se lit FALSE : allumées.
				'lettrines' => [
					'label'   => $this->lang('Lettrines'),
					'checked' => ['on' => $this->config->granite_lettrines === FALSE || (bool) $this->config->granite_lettrines],
					'values'  => ['on' => $this->lang('Orner d’une capitale la première lettre de la une et des articles')],
					'type'    => 'checkbox'
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
			if ($post['header']) $this->config('granite_header', $post['header'], 'int');
			else                 $this->config->unset('granite_header');

			if ($post['logo'])   $this->config('granite_logo', $post['logo'], 'int');
			else                 $this->config->unset('granite_logo');

			$this	->config('granite_header_repeat',     $post['repeat'])
					->config('granite_header_attachment', in_array('on', $post['fixed']) ? 'fixed' : 'scroll')
					->config('granite_header_position',   $post['positionX'].' '.$post['positionY'])
					->config('granite_header_color',      $post['color'])
					->config('nf_version_css',            time());

			notify($this->lang('Titre du journal mis à jour !'));
			redirect($this->url->location.'#header');
		}
		else if ($form_background->is_valid($post))
		{
			if ($post['background']) $this->config('granite_background', $post['background'], 'int');
			else                     $this->config->unset('granite_background');

			$this	->config('granite_background_repeat',     $post['repeat'])
					->config('granite_background_attachment', in_array('on', $post['fixed']) ? 'fixed' : 'scroll')
					->config('granite_background_position',   $post['positionX'].' '.$post['positionY'])
					->config('granite_background_color',      $post['color'])
					->config('nf_version_css',                time());

			notify($this->lang('Arrière-plan mis à jour !'));
			redirect($this->url->location.'#background');
		}
		else if ($form_settings->is_valid($post))
		{
			$this	->config('granite_theme_color', $post['theme_color'])
					->config('granite_text_color',  $post['text_color'])
					->config('granite_lettrines',   in_array('on', $post['lettrines']) ? 1 : 0, 'int')
					->config('nf_version_css',      time());

			// Le réglage de la barre du haut fixe, retiré avec la 2.0.0.
			$this->config->unset('granite_navbar_display');

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
