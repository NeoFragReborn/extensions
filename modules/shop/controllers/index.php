<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Boutique — page publique (catalogue + achat).
 */

namespace NF\Modules\Shop\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		// La page est faite des articles de la boutique, qui n'ont pas de langue à eux : sa canonique est
		// dans la langue première du site.
		nf_seo_sans_langue();

		$this->css('shop')->js('shop');

		$gam     = $this->module('gamification');
		$logged  = (bool)$this->user();
		$user_id = $logged ? (int)$this->user->id : 0;
		$balance = ($gam && $logged) ? $gam->get_points($user_id) : 0;

		$items = $this->db	->select('*')
							->from('nf_shop_items')
							->where('active', 1)
							->order_by('position', 'id')
							->get(FALSE);

		$owned = [];
		if ($logged)
		{
			foreach ($this->db->select('item_id')->from('nf_shop_purchases')->where('user_id', $user_id)->get() as $iid)
			{
				$owned[(int)$iid] = TRUE;
			}
		}

		return $this->view('index', [
			'items'   => $items,
			'owned'   => $owned,
			'balance' => $balance,
			'logged'  => $logged,
			// Le jeton des actions qui modifient : l'achat l'exige (cf. Ajax::_buy).
			// csrf: vérifié par modules/shop/controllers/ajax.php
			'jeton'   => $logged ? (string) $this->csrf_token() : ''
		]);
	}
}
