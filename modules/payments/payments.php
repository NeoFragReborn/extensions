<?php
/**
 * https://neofr.ag
 * Module Paiements (Stripe) — recharge de points + packs VIP en argent réel.
 *
 * SÉCURITÉ : inerte tant que les clés ne sont pas configurées. Le webhook vérifie
 * la signature Stripe (HMAC-SHA256 horodaté, anti-rejeu) et l'idempotence
 * (event_id unique) avant de créditer. À relire en revue sécu + tester avec de
 * vraies clés (mode test) avant toute mise en production.
 */

namespace NF\Modules\Payments;

use NF\NeoFrag\Addons\Module;

class Payments extends Module
{
	const SIGNATURE_TOLERANCE = 300; // secondes

	protected function __info()
	{
		return [
			'title'       => $this->lang('Paiements'),
			'description' => $this->lang('Recharge de points et packs VIP via Stripe (argent réel).'),
			'icon'        => 'fas fa-credit-card',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				''                  => 'index',
				'success'           => '_success',
				'cancel'            => '_cancel',
				'webhook'           => '_webhook',
				'ajax/checkout/{id}'=> '_checkout',
				'admin'             => 'index',
				'admin/packs/new'   => '_pack_new',
				'admin/packs/edit/{id}'   => '_pack_edit',
				'admin/packs/delete/{id}' => '_pack_delete',
			]
		];
	}

	/** Stripe est-il configuré et activé ? (NB : PAS is_enabled — réservé au routage des addons). */
	public function is_configured()
	{
		return (bool)$this->config->pay_stripe_enabled && !empty($this->config->pay_stripe_secret);
	}

	/** Packs achetables actifs. */
	public function packs()
	{
		return $this->db->select('*')->from('nf_payment_packs')->where('active', 1)->order_by('position', 'id')->get(FALSE);
	}

	public function pack($id)
	{
		return $this->db->select('*')->from('nf_payment_packs')->where('id', (int)$id)->where('active', 1)->row(FALSE);
	}

	private function abs_url($path)
	{
		return ($this->url->https ? 'https' : 'http').'://'.$this->url->host.$this->url->base.$path;
	}

	/** Crée une session Stripe Checkout (API directe). @return string|null URL de paiement */
	public function create_checkout_session($pack, $user_id)
	{
		if (!$this->is_configured() || !$pack)
		{
			return NULL;
		}

		$params = [
			'mode'                => 'payment',
			'success_url'         => $this->abs_url('payments/success'),
			'cancel_url'          => $this->abs_url('payments/cancel'),
			'client_reference_id' => (string)(int)$user_id,
			'metadata'            => [
				'user_id' => (string)(int)$user_id,
				'kind'    => $pack['kind'],
				'units'   => (string)(int)$pack['units'],
			],
			'line_items'          => [[
				'quantity'   => 1,
				'price_data' => [
					'currency'     => $pack['currency'] ?: 'eur',
					'unit_amount'  => (int)$pack['price_cents'],
					'product_data' => ['name' => $pack['label']],
				],
			]],
		];

		$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
		curl_setopt_array($ch, [
			CURLOPT_POST           => TRUE,
			CURLOPT_POSTFIELDS     => http_build_query($params),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$this->config->pay_stripe_secret],
		]);

		$resp = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($code !== 200 || !$resp)
		{
			return NULL;
		}

		$data = json_decode($resp, TRUE);

		return $data['url'] ?? NULL;
	}

	/** Vérifie la signature Stripe d'un webhook (HMAC-SHA256 horodaté + anti-rejeu). */
	public function verify_signature($payload, $header, $secret)
	{
		if (empty($secret) || empty($header))
		{
			return FALSE;
		}

		$t = $v1 = NULL;
		foreach (explode(',', $header) as $kv)
		{
			$p = explode('=', $kv, 2);
			if (count($p) === 2)
			{
				if ($p[0] === 't')  $t  = $p[1];
				if ($p[0] === 'v1') $v1 = $p[1];
			}
		}

		if (!$t || !$v1 || abs(time() - (int)$t) > self::SIGNATURE_TOLERANCE)
		{
			return FALSE;
		}

		return hash_equals(hash_hmac('sha256', $t.'.'.$payload, $secret), $v1);
	}

	/**
	 * Traite un paiement validé (idempotent). @return bool crédité
	 * Insère d'abord la ligne idempotence (clé unique event_id) : si l'événement a
	 * déjà été traité, l'insert échoue et on ne crédite pas deux fois.
	 */
	public function fulfill($event_id, $session_id, $user_id, $kind, $units)
	{
		$user_id = (int)$user_id;
		$units   = (int)$units;

		if (!$event_id || !$user_id || $units <= 0 || !in_array($kind, ['points', 'vip'], TRUE))
		{
			return FALSE;
		}

		// Idempotence : déjà traité ?
		if (!$this->db->from('nf_payments')->where('event_id', $event_id)->empty())
		{
			return FALSE;
		}

		// La clé unique event_id garantit l'unicité même en cas de course (2 webhooks).
		try
		{
			$this->db->insert('nf_payments', [
				'event_id'   => $event_id,
				'session_id' => (string)$session_id,
				'user_id'    => $user_id,
				'kind'       => $kind,
				'units'      => $units,
				'status'     => 'completed',
			]);
		}
		catch (\Throwable $e)
		{
			return FALSE; // inséré entre-temps → pas de double crédit
		}

		$gam = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);

		if ($gam)
		{
			if ($kind === 'points')
			{
				$gam->add_points($user_id, $units, 'stripe', $this->lang('Recharge Stripe'));
			}
			else
			{
				$gam->grant_vip($user_id, $units, 'stripe');
			}
		}

		return TRUE;
	}
}
