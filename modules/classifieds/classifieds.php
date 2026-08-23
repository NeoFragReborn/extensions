<?php
/**
 * https://neofr.ag
 * Module Petites annonces — annonces membres (offre/demande) catégorisées,
 * contact vendeur via notification, modération a priori optionnelle.
 */

namespace NF\Modules\Classifieds;

use NF\NeoFrag\Addons\Module;

class Classifieds extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Petites annonces'),
			'description' => $this->lang('Annonces entre membres (offres / demandes) classées par catégorie, avec contact vendeur.'),
			'icon'        => 'fas fa-bullhorn',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                   => 'index',
				'new'                                => '_new',
				'category/{id}/{url_title}'          => '_category',
				'{id}/{url_title}'                   => '_show',
				'{id}/{url_title}/edit'              => '_edit',
				'{id}/{url_title}/close'            => '_close',
				'{id}/{url_title}/contact'          => '_contact',
				'admin{pages}'                       => 'index',
				'admin/cat/add'                      => '_cat_add',
				'admin/cat/{id}/{url_title}'         => '_cat_edit',
				'admin/cat/delete/{id}/{url_title}'  => '_cat_delete',
				'admin/{id}/{url_title}/approve'     => '_approve',
				'admin/{id}/{url_title}/reject'      => '_reject',
				'admin/delete/{id}/{url_title}'      => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Petites annonces',
						'icon'   => 'fas fa-bullhorn',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les annonces et catégories'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	public static function type_label($type)
	{
		$L = NeoFrag();
		return [
			'offer'   => '<span class="badge text-bg-success"><i class="fas fa-tag"></i> '.$L->lang('Offre').'</span>',
			'request' => '<span class="badge text-bg-info"><i class="fas fa-search"></i> '.$L->lang('Recherche').'</span>'
		][$type] ?? $type;
	}

	public static function status_label($status)
	{
		$L = NeoFrag();
		return [
			'pending'   => '<span class="badge text-bg-warning"><span class="dot"></span> '.$L->lang('En attente').'</span>',
			'published' => '<span class="badge text-bg-success"><span class="dot"></span> '.$L->lang('Publiée').'</span>',
			'closed'    => '<span class="badge text-bg-secondary"><span class="dot"></span> '.$L->lang('Clôturée').'</span>',
			'rejected'  => '<span class="badge text-bg-danger"><span class="dot"></span> '.$L->lang('Refusée').'</span>'
		][$status] ?? $status;
	}

	public static function format_price($price, $lang)
	{
		if ($price === NULL || $price === '')
		{
			return '<span class="text-muted">'.$lang->lang('À débattre').'</span>';
		}
		$p = (float)$price;
		if ($p <= 0)
		{
			return '<span class="badge text-bg-success">'.$lang->lang('Gratuit').'</span>';
		}
		return '<strong>'.number_format($p, 2, ',', ' ').' €</strong>';
	}
}
