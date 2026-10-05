<?php
declare(strict_types=1);
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
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
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
						'title'  => $this->lang('Petites annonces'),
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
		// Au nom du MODULE : NeoFrag() seul cherchait ces libellés dans les traductions du cœur, qui ne
		// les ont pas — « Offre » et « Refusée » restaient en français sur le site anglais (2026-09-23).
		$L = NeoFrag()->module('classifieds');
		return [
			'offer'   => '<span class="badge text-bg-success"><i class="fas fa-tag"></i> '.$L->lang('Offre').'</span>',
			'request' => '<span class="badge text-bg-info"><i class="fas fa-search"></i> '.$L->lang('Recherche').'</span>'
		][$type] ?? $type;
	}

	public static function status_label($status)
	{
		$L = NeoFrag()->module('classifieds');
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

	/**
	 * L'onglet « Petites annonces » du profil public d'un membre (User::onglets_profil(), chantier A, étape A2) : ses
	 * annonces publiées, les plus récentes d'abord. Aucune : pas d'onglet.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function profil_membre($membre): array
	{
		$annonces = (array) $this->db	->select('a.id', 'a.title', 'a.ad_type', 'a.price', 'a.created_at', 'c.title AS categorie')
										->from('nf_classifieds a')
										->join('nf_classifieds_categories c', 'c.id = a.category_id')
										->where('a.user_id', (int) $membre->id)
										->where('a.status', 'published')
										->order_by('a.created_at DESC')
										->limit(20)
										->get();

		if (!$annonces)
		{
			return [];
		}

		return [[
			'onglet'  => 'annonces',
			'titre'   => (string) $this->lang('Petites annonces'),
			'icone'   => 'fas fa-bullhorn',
			'ordre'   => 40,
			'nombre'  => (int) $this->db->from('nf_classifieds')->where('user_id', (int) $membre->id)->where('status', 'published')->count(),
			'contenu' => fn () => $this->view('profil-membre', ['annonces' => $annonces]),
		]];
	}
}
