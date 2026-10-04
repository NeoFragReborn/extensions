<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Boutique — endpoint AJAX d'achat : /ajax/shop/buy/{id}.
 * L'adresse commence par `ajax/` : c'est ce qui fait choisir CE contrôleur. Écrite
 * `shop/ajax/buy/{id}`, elle désignait le contrôleur public, qui n'a pas `_buy` : le bouton
 * « Acheter » recevait un 404 (relevé le 2026-10-04).
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
			$this->_refus('login');
		}

		// Un achat dépense des points : il exige le jeton de session des actions qui modifient
		// (`csrf_token()`), que la page de la boutique pose dans l'adresse du bouton. Sans lui, une page
		// tierce pouvait faire acheter un membre connecté à son insu (relevé le 2026-10-04).
		if (!$this->csrf_valide())
		{
			$this->_refus('csrf');
		}

		$id      = (int)$id;
		$user_id = (int)$this->user->id;

		$item = $this->db->select('*')->from('nf_shop_items')->where('id', $id)->where('active', 1)->row(FALSE);

		if (!$item)
		{
			$this->_refus('not_found');
		}

		$gam = $this->module('gamification');

		// couplage(gamification): facultatif — sans le module, `instanceof` est faux et l'achat est refusé (« unavailable »).
		if (!$gam instanceof \NF\Modules\Gamification\Gamification || !$gam->is_enabled())
		{
			$this->_refus('unavailable');
		}

		$prix = max(0, (int)$item['price']);

		/*
		 * L'achat se fait en UNE transaction, et chaque écriture qui peut manquer est jugée par la base.
		 *
		 * Avant, tout était lu puis réécrit : le solde vérifié puis débité, le stock lu puis remplacé
		 * par « stock - 1 », la possession vérifiée avant le paiement. Deux achats simultanés passaient
		 * tous les deux — deux objets pour un solde qui n'en payait qu'un, un stock qui ne descendait
		 * que d'une unité, un objet « unique » acheté deux fois.
		 */
		$this->db->transaction();

		try
		{
			// 1. Le débit, vérifié et fait par la même requête (Gamification::spend_points). La ligne de
			//    points du membre reste verrouillée jusqu'à la fin : ses achats simultanés passent l'un
			//    après l'autre. Un objet gratuit ne débite rien.
			if ($prix > 0 && !$gam->spend_points($user_id, $prix, $this->lang('Achat boutique : %s', $item['title'])))
			{
				$this->db->rollback();
				$this->_refus('insufficient', ['balance' => $gam->get_points($user_id)]);
			}

			// 2. Déjà possédé ? Lu APRÈS le verrou : un second achat simultané voit le premier.
			if (!empty($item['unique_per_user']) && !$this->db->from('nf_shop_purchases')->where('user_id', $user_id)->where('item_id', $id)->empty())
			{
				$this->db->rollback();
				$this->_refus('owned');
			}

			// 3. Le stock, s'il est compté (-1 = illimité) : décrémenté par la base, jamais sous zéro.
			if ((int)$item['stock'] >= 0 && !$this->db->where('id', $id)->where('stock >', 0)->update('nf_shop_items', 'stock = stock - 1'))
			{
				$this->db->rollback();
				$this->_refus('stock');
			}

			$this->db->insert('nf_shop_purchases', [
				'user_id'    => $user_id,
				'item_id'    => $id,
				'price_paid' => $prix
			]);

			$this->_apply_effect($user_id, $item, $gam);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		echo json_encode(['ok' => TRUE, 'balance' => $gam->get_points($user_id)]);
		exit;
	}

	/**
	 * Refuse l'achat : la raison en JSON, que `shop.js` traduit pour le membre.
	 *
	 * @param array<string, mixed> $plus
	 */
	private function _refus(string $erreur, array $plus = []): never
	{
		echo json_encode(['ok' => FALSE, 'error' => $erreur] + $plus);
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
