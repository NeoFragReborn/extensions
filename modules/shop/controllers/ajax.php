<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Boutique — endpoint AJAX d'achat : /shop/ajax/buy/{id}.
 */

namespace NF\Modules\Shop\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function _buy($id)
	{
		header('Content-Type: application/json');

		if (!$this->user())
		{
			echo json_encode(['ok' => FALSE, 'error' => 'login']);
			exit;
		}

		$id      = (int)$id;
		$user_id = (int)$this->user->id;

		$item = $this->db->select('*')->from('nf_shop_items')->where('id', $id)->where('active', 1)->row(FALSE);

		if (!$item)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'not_found']);
			exit;
		}

		if (!empty($item['unique_per_user']) && !$this->db->from('nf_shop_purchases')->where('user_id', $user_id)->where('item_id', $id)->empty())
		{
			echo json_encode(['ok' => FALSE, 'error' => 'owned']);
			exit;
		}

		if ((int)$item['stock'] === 0)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'stock']);
			exit;
		}

		$gam = $this->module('gamification');

		if (!$gam)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'unavailable']);
			exit;
		}

		if (!$gam->spend_points($user_id, (int)$item['price'], $this->lang('Achat boutique : %s', $item['title'])))
		{
			echo json_encode(['ok' => FALSE, 'error' => 'insufficient', 'balance' => $gam->get_points($user_id)]);
			exit;
		}

		$this->db->insert('nf_shop_purchases', [
			'user_id'    => $user_id,
			'item_id'    => $id,
			'price_paid' => (int)$item['price']
		]);

		if ((int)$item['stock'] > 0)
		{
			$this->db->where('id', $id)->update('nf_shop_items', ['stock' => (int)$item['stock'] - 1]);
		}

		$this->_apply_effect($user_id, $item, $gam);

		echo json_encode(['ok' => TRUE, 'balance' => $gam->get_points($user_id)]);
		exit;
	}

	/** Applique l'effet de l'item acheté. Grade (groupe) + VIP câblés ; autres = possession enregistrée. */
	private function _apply_effect($user_id, $item, $gam)
	{
		if ($item['type'] === 'group' && ($group_id = (int)$item['payload']))
		{
			if ($this->db->from('nf_users_groups')->where('user_id', $user_id)->where('group_id', $group_id)->empty())
			{
				$this->db->insert('nf_users_groups', [
					'user_id'  => $user_id,
					'group_id' => $group_id
				]);

				// Le fil d'événements de l'API : le membre a gagné un groupe.
				// couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
				$this->events->fire('user.groups.changed', ['user_id' => (int) $user_id]);

				if (($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
				{
					$api->consigner('user.groups.changed', ['user_id' => (int) $user_id]);
				}
			}
		}
		else if ($item['type'] === 'vip' && ($days = (int)$item['payload']))
		{
			$gam->grant_vip($user_id, $days, 'shop');
		}

		// 'cosmetic'/'perk'/'merch' → possession enregistrée (consommée par les fonctionnalités
		// concernées : régie pub, profil, fulfilment merch).
	}
}
