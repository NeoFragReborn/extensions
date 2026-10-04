<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Paiements — création de la session Stripe Checkout (AJAX) : /ajax/payments/checkout/{id}.
 * L'adresse commence par `ajax/` : c'est ce qui fait choisir CE contrôleur. Écrite
 * `payments/ajax/checkout/{id}`, elle désignait le contrôleur public, qui n'a pas `_checkout` :
 * le bouton « Payer » recevait un 404 (relevé le 2026-10-04).
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

		// Le jeton des actions qui modifient, que la page des paiements pose dans l'adresse du bouton —
		// comme l'achat de la boutique (cf. Shop\Controllers\Ajax::_buy).
		if (!$this->csrf_valide())
		{
			echo json_encode(['ok' => FALSE, 'error' => 'csrf']);
			exit;
		}

		$pay = $this->module('payments');

		if (!$pay instanceof \NF\Modules\Payments\Payments || !$pay->vente_possible())
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
