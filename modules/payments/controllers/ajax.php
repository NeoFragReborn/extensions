<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Paiements — création de la session Stripe Checkout (AJAX) : /payments/ajax/checkout/{id}.
 */

namespace NF\Modules\Payments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function _checkout($id)
	{
		header('Content-Type: application/json');

		if (!$this->user())
		{
			echo json_encode(['ok' => FALSE, 'error' => 'login']);
			exit;
		}

		$pay = $this->module('payments');

		if (!$pay || !$pay->is_configured())
		{
			echo json_encode(['ok' => FALSE, 'error' => 'disabled']);
			exit;
		}

		$pack = $pay->pack((int)$id);

		if (!$pack)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'not_found']);
			exit;
		}

		$url = $pay->create_checkout_session($pack, (int)$this->user->id);

		if (!$url)
		{
			echo json_encode(['ok' => FALSE, 'error' => 'stripe']);
			exit;
		}

		echo json_encode(['ok' => TRUE, 'url' => $url]);
		exit;
	}
}
