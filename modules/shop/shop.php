<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Boutique — catalogue + achats. Découplé de gamification : paiement
 * enfichable (points via gamification maintenant, argent réel/merch plus tard).
 * Effets à l'achat : grade (groupe), cosmétique, perk, VIP, merch.
 */

namespace NF\Modules\Shop;

use NF\NeoFrag\Addons\Module;

class Shop extends Module
{
	/*
	 * Les types d'item : des clés techniques, sans libellé. Une constante ne peut pas appeler lang() ;
	 * elle portait le libellé français, que l'administration retraduisait par un appel DYNAMIQUE
	 * qu'aucun contrôle ne suivait. Pour AFFICHER un type : type_labels(), ci-dessous.
	 */
	const TYPES = ['group', 'cosmetic', 'perk', 'vip', 'merch'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Boutique'),
			'description' => $this->lang('Boutique de biens virtuels (grades, cosmétiques, VIP…) payables en points, et merch.'),
			'icon'        => 'fas fa-store',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				''                  => 'index',
				'ajax/buy/{id}'     => '_buy',
				'admin'             => 'index',
				'admin/new'         => '_new',
				'admin/edit/{id}'   => '_edit',
				'admin/delete/{id}' => '_delete',
			]
		];
	}

	/** Les libellés TRADUITS des types d'item, par clé de TYPES. */
	public function type_labels()
	{
		return [
			'group'    => $this->lang('Grade (groupe affiché sur le profil)'),
			'cosmetic' => $this->lang('Cosmétique'),
			'perk'     => $this->lang('Avantage (perk)'),
			'vip'      => $this->lang('VIP (payload = jours)'),
			'merch'    => $this->lang('Produit physique (merch)'),
		];
	}

	/** Items actifs du catalogue (tableaux associatifs). */
	public function items()
	{
		return $this->db	->select('*')
							->from('nf_shop_items')
							->where('active', 1)
							->order_by('position', 'id')
							->get(FALSE);
	}

	/** Un item par id. */
	public function item($id)
	{
		return $this->db->select('*')->from('nf_shop_items')->where('id', (int)$id)->row(FALSE);
	}

	/** L'utilisateur possède-t-il déjà cet item ? */
	public function owns($user_id, $item_id)
	{
		return (bool)$user_id && !$this->db	->from('nf_shop_purchases')
											->where('user_id', (int)$user_id)
											->where('item_id', (int)$item_id)
											->empty();
	}

	/** IDs des items possédés par un membre (pour l'affichage en lot). */
	public function owned_ids($user_id)
	{
		if (!$user_id)
		{
			return [];
		}

		return array_map('intval', $this->db	->select('item_id')
												->from('nf_shop_purchases')
												->where('user_id', (int)$user_id)
												->get());
	}

	/** Le membre possède-t-il un perk donné (item type 'perk' avec ce payload) ? Ex. 'no_ads'. */
	public function has_perk($user_id, $perk_key)
	{
		$user_id = (int)$user_id;

		return (bool)$user_id && !$this->db	->from('nf_shop_purchases p')
											->join('nf_shop_items i', 'i.id = p.item_id')
											->where('p.user_id', $user_id)
											->where('i.type', 'perk')
											->where('i.payload', (string)$perk_key)
											->empty();
	}
}
