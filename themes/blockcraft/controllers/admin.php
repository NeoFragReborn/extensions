<?php
/**
 * https://neofr.ag
 * Blockcraft — panneau d'administration du thème : l'en-tête (le logo, l'adresse du serveur), l'arrière-plan, les
 * couleurs et les réseaux sociaux. Le ciel et le paysage sont dessinés : il n'y a plus d'image de bannière (1.x).
 */

namespace NF\Themes\Blockcraft\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index()
	{
		$this->js('admin');

		// Les couleurs par défaut de Blockcraft 1.x valent celles de la 2.0 (cf. css/style.css) : le sélecteur montre la
		// couleur que le thème rend.
		$heritees = ['#6aa84f' => '#3c7a27', '#eef3f7' => '#eef3e6', '#2e2a25' => '#1e2916'];
		$couleur  = static fn ($valeur): string => $heritees[strtolower((string) $valeur)] ?? (string) $valeur;

		$image_check = function($filename, $ext){
			if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png']))
			{
				return $this->lang('Please choose an image file');
			}
		};

		/* ------------------------------------------------- L'en-tête : le logo, l'adresse du serveur */

		$form_header = $this->form()
			->add_rules([
				'logo' => [
					'label'       => $this->lang('Logo du site'),
					'value'       => $this->config->blockcraft_logo,
					'type'        => 'file',
					'upload'      => 'themes/blockcraft/logos',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Dans la barre, devant le nom du site. Laissé vide : le logo du site (réglages généraux), s’il y en a un, sinon un bloc d’herbe.')
				],
				// L'adresse que les joueurs tapent dans le jeu, dans le ciel de l'accueil, avec son bouton « Copier l'adresse ».
				'adresse' => [
					'label'       => $this->lang('Adresse du serveur'),
					'value'       => $this->config->blockcraft_adresse,
					'type'        => 'text',
					'description' => $this->lang('Celle que les joueurs tapent dans le jeu (par exemple play.monserveur.fr). Elle s’affiche sous le nom du site, sur l’accueil, avec un bouton pour la copier. Laisser vide : pas d’adresse.')
				]
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		/* ----------------------------------------------------------- Background */

		$form_background = $this->form()
			->add_rules([
				'background' => [
					'label'       => $this->lang('Image de fond'),
					'value'       => $this->config->blockcraft_background,
					'type'        => 'file',
					'upload'      => 'themes/blockcraft/backgrounds',
					'info'        => $this->lang(' d\'image (max. %d Mo)', file_upload_max_size() / 1024 / 1024),
					'check'       => $image_check,
					'description' => $this->lang('Laisser vide pour utiliser la couleur unie du thème.')
				],
				'repeat' => [
					'label'  => $this->lang('Répétition'),
					'value'  => $this->config->blockcraft_background_repeat,
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
					'value'  => explode(' ', (string) $this->config->blockcraft_background_position ?: 'center top')[0],
					'values' => [
						'left'   => $this->lang('Gauche'),
						'center' => $this->lang('Centré'),
						'right'  => $this->lang('Droite')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'positionY' => [
					'value'  => explode(' ', (string) $this->config->blockcraft_background_position ?: 'center top')[1] ?? 'top',
					'values' => [
						'top'    => $this->lang('Haut'),
						'center' => $this->lang('Milieu'),
						'bottom' => $this->lang('Bas')
					],
					'type'   => 'radio',
					'rules'  => 'required'
				],
				'fixed' => [
					'checked' => ['on' => $this->config->blockcraft_background_attachment == 'fixed'],
					'values'  => ['on' => $this->lang('Image fixe (parallax)')],
					'type'    => 'checkbox'
				],
				'color' => [
					'label' => $this->lang('Couleur de fond'),
					'value' => $couleur($this->config->blockcraft_background_color),
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
					'value'       => $couleur($this->config->blockcraft_theme_color),
					'type'        => 'colorpicker',
					'description' => $this->lang('L’herbe du thème : les boutons, les liens, les titres colorés et les widgets colorés.'),
					'rules'       => 'required',
					'size'        => 'col-3'
				],
				'text_color' => [
					'label'       => $this->lang('Couleur du texte'),
					'value'       => $couleur($this->config->blockcraft_text_color),
					'type'        => 'colorpicker',
					'description' => $this->lang('Couleur appliquée au texte principal'),
					'rules'       => 'required',
					'size'        => 'col-3'
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
			if ($post['logo']) $this->config('blockcraft_logo', $post['logo'], 'int');
			else               $this->config->unset('blockcraft_logo');

			// Une adresse de serveur : une ligne, sans balise ; au-delà de cent caractères, ce n'en est pas une.
			$this	->config('blockcraft_adresse', mb_substr(trim(strip_tags((string) $post['adresse'])), 0, 100))
					->config('nf_version_css', time());

			notify($this->lang('En-tête mis à jour !'));
			redirect($this->url->location.'#header');
		}
		else if ($form_background->is_valid($post))
		{
			if ($post['background']) $this->config('blockcraft_background', $post['background'], 'int');
			else                     $this->config->unset('blockcraft_background');

			$this	->config('blockcraft_background_repeat',     $post['repeat'])
					->config('blockcraft_background_attachment', in_array('on', $post['fixed']) ? 'fixed' : 'scroll')
					->config('blockcraft_background_position',   $post['positionX'].' '.$post['positionY'])
					->config('blockcraft_background_color',      $post['color'])
					->config('nf_version_css',                    time());

			notify($this->lang('Arrière-plan mis à jour !'));
			redirect($this->url->location.'#background');
		}
		else if ($form_settings->is_valid($post))
		{
			$this	->config('blockcraft_theme_color', $post['theme_color'])
					->config('blockcraft_text_color',  $post['text_color'])
					->config('nf_version_css',         time());

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
