<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Webradio — un lecteur de flux et une grille d'émissions.
 *
 * Cinquième des six ajouts de un chantier interne. Contrairement à la carte des lieux, celui-ci demande
 * VRAIMENT quelque chose à la politique de sécurité du site.
 *
 * Pourquoi : la politique ne déclarait aucune directive `media-src`, qui héritait donc de
 * `default-src 'self'`. Un flux de webradio est par nature distant — il aurait été bloqué, sans que
 * rien à l'écran ne l'explique. `index.php` déclare désormais `media-src`, et n'y ajoute l'origine
 * du flux QUE si un flux est configuré : un site sans webradio ne déclare aucune origine tierce de
 * plus, exactement comme il n'en déclare aucune pour Analytics tant qu'aucun identifiant n'est posé.
 *
 * L'origine est réduite à son schéma, son hôte et son port par `Schedule::origine()`, qui refuse
 * tout hôte contenant autre chose que des lettres, des chiffres, un point ou un tiret : l'espace
 * sépare les sources dans cet en-tête, et un hôte qui en contiendrait permettrait d'y glisser une
 * seconde source.
 */

namespace NF\Modules\Webradio;

use NF\NeoFrag\Addons\Module;
use NF\Modules\Webradio\Lib\Schedule;

class Webradio extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Webradio'),
			'description' => $this->lang('Lecteur d\'un flux de webradio et grille des émissions de la semaine.'),
			'icon'        => 'fas fa-broadcast-tower',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                => 'index',
				'admin{pages}'                    => 'index',
				'admin/s/add'                     => '_s_add',
				'admin/s/{id}/{url_title}'        => '_s_edit',
				'admin/s/delete/{id}/{url_title}' => '_s_delete'
			],
			'settings'    => function(){
				return $this	->form2()
								->rule($this->form_text('webradio_name')
											->title($this->lang('Nom de la station'))
											->value((string) ($this->config->webradio_name ?: ''))
								)
								->rule($this->form_text('webradio_stream')
											->title($this->lang('Adresse du flux'))
											->value((string) ($this->config->webradio_stream ?: ''))
								)
								->rule($this->form_text('webradio_site')
											->title($this->lang('Site de la station'))
											->value((string) ($this->config->webradio_site ?: ''))
								)
								->success(function($data){
									$flux = Schedule::flux($data['webradio_stream'] ?? '');

									$this->config('webradio_name', trim((string) ($data['webradio_name'] ?? '')));
									$this->config('webradio_stream', $flux);
									$this->config('webradio_site', Schedule::flux($data['webradio_site'] ?? ''));

									// L'origine est rangée à part : c'est elle, et elle seule, que lit
									// `index.php` au moment d'écrire la politique de sécurité. La
									// calculer une fois ici évite de le faire à chaque requête.
									$this->config('webradio_origin', Schedule::origine($flux));

									if (($data['webradio_stream'] ?? '') !== '' && $flux === '')
									{
										notify($this->lang('Adresse de flux invalide : seuls http et https sont acceptés.'));
									}
									else if ($flux !== '' && Schedule::contenu_mixte($flux))
									{
										// Le navigateur refusera un flux `http` sur un site `https`. Mieux
										// vaut le dire ici que laisser chercher pourquoi rien ne sort.
										notify($this->lang('Attention : un flux en http sera refusé par les navigateurs sur un site en https.'));
									}
									else
									{
										notify($this->lang('Configuration modifiée'));
									}

									refresh();
								});
			}
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Webradio'),
						'icon'   => 'fas fa-broadcast-tower',
						'access' => [
							'manage_shows' => ['title' => $this->lang('Gérer les émissions'), 'icon' => 'fas fa-microphone', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
