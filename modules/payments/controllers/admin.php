<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Paiements — admin : réglages Stripe (clés) + catalogue des packs.
 */

namespace NF\Modules\Payments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this->subtitle($this->lang('Stripe'))->icon('fas fa-credit-card');

		$form = $this->form()
			->add_rules([
				'enabled' => [
					'type'    => 'checkbox',
					'checked' => ['on' => $this->config->pay_stripe_enabled],
					'values'  => ['on' => $this->lang('Activer les paiements Stripe')]
				],
				'public' => ['label' => $this->lang('Clé publique (publishable)'), 'value' => $this->config->pay_stripe_public],
				'secret' => ['label' => $this->lang('Clé secrète'),                'value' => $this->config->pay_stripe_secret],
				'webhook_secret' => [
					'label'       => $this->lang('Secret de signature du webhook'),
					'value'       => $this->config->pay_stripe_webhook_secret,
					'description' => $this->lang('Dans Stripe, crée un webhook sur <code>%s</code> pour l\'événement <code>checkout.session.completed</code>, et colle ici son secret de signature.', url('payments/webhook'))
				],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$this	->config('pay_stripe_enabled',        in_array('on', (array)$post['enabled']), 'bool')
					->config('pay_stripe_public',         $post['public'])
					->config('pay_stripe_secret',         $post['secret'])
					->config('pay_stripe_webhook_secret', $post['webhook_secret']);

			notify($this->lang('Réglages Stripe enregistrés'));
			redirect('admin/payments');
		}

		$packs = $this->db->select('*')->from('nf_payment_packs')->order_by('position', 'id')->get(FALSE);

		$rows = '';
		foreach ($packs as $p)
		{
			$rows .= '<tr>'
				.'<td>'.htmlspecialchars((string) ($p['label'])).'</td>'
				.'<td>'.($p['kind'] === 'vip' ? $this->lang('VIP (%d j)', (int)$p['units']) : $this->lang('%d points', (int)$p['units'])).'</td>'
				.'<td class="text-end">'.number_format((int)$p['price_cents'] / 100, 2).' '.strtoupper($p['currency']).'</td>'
				.'<td class="text-center">'.(!empty($p['active']) ? '<span class="badge text-bg-success">'.$this->lang('Actif').'</span>' : '<span class="badge text-bg-secondary">'.$this->lang('Inactif').'</span>').'</td>'
				.'<td class="text-end">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/payments/packs/edit/'.(int)$p['id']).'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/payments/packs/delete/'.(int)$p['id']).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ce pack ?')), ENT_QUOTES).'"><i class="far fa-trash-alt"></i></a>'
				.'</td>'
				.'</tr>';
		}

		$packs_body = $packs
			? '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>'.$this->lang('Pack').'</th><th>'.$this->lang('Contenu').'</th><th class="text-end">'.$this->lang('Prix').'</th><th class="text-center">'.$this->lang('Statut').'</th><th></th></tr></thead><tbody>'.$rows.'</tbody></table></div>'
			: $this->admin_empty('fas fa-box', $this->lang('Aucun pack.'));

		// Sans Gamification, rien ne crédite un achat : la vente est fermée (Payments::vente_possible),
		// et l'administrateur doit savoir pourquoi la page publique dit « indisponible ».
		$alerte = ($pay = $this->module('payments')) instanceof \NF\Modules\Payments\Payments && !$pay->gamification()
			? '<div class="alert alert-warning">'.icon('fas fa-exclamation-triangle').' '.$this->lang('Les points et le VIP vendus ici sont crédités par le module Gamification, absent ou désactivé : les achats restent fermés tant qu\'il n\'est pas installé et activé.').'</div>'
			: '';

		return $alerte
			.$this->admin_card('fas fa-credit-card', $this->lang('Réglages Stripe'), $form->display())
			.$this->admin_card('fas fa-box', $this->lang('Packs'), $packs_body, '', $this->admin_create('admin/payments/packs/new', $this->lang('Nouveau pack')));
	}

	public function _pack_new()
	{
		return $this->_pack_form(NULL);
	}

	public function _pack_edit($id)
	{
		return $this->_pack_form((int)$id);
	}

	private function _pack_form($id)
	{
		$pack = $id ? $this->db->select('*')->from('nf_payment_packs')->where('id', $id)->row(FALSE) : NULL;

		if ($id && !$pack)
		{
			$this->error(404);
			return;
		}

		$this->subtitle($id ? $this->lang('Modifier le pack') : $this->lang('Nouveau pack'))->icon('fas fa-box');
		$this->breadcrumb($this->lang('Paiements'), 'admin/payments');

		$form = $this->form()
			->add_rules([
				'label'       => ['label' => $this->lang('Libellé'),  'value' => $pack['label'] ?? '',        'rules' => 'required'],
				'kind'        => ['label' => $this->lang('Type'),     'value' => $pack['kind'] ?? 'points',   'type'  => 'select', 'values' => ['points' => $this->lang('Points'), 'vip' => $this->lang('VIP (jours)')]],
				'units'       => ['label' => $this->lang('Quantité (points ou jours de VIP)'), 'value' => $pack['units'] ?? 0, 'type' => 'number', 'rules' => 'required', 'size' => 'col-3'],
				'price_cents' => ['label' => $this->lang('Prix (centimes)'), 'value' => $pack['price_cents'] ?? 0, 'type' => 'number', 'rules' => 'required', 'size' => 'col-3', 'description' => $this->lang('Ex. 499 = 4,99')],
				'currency'    => ['label' => $this->lang('Devise'),   'value' => $pack['currency'] ?? 'eur',  'type'  => 'select', 'values' => ['eur' => 'EUR', 'usd' => 'USD', 'gbp' => 'GBP', 'cad' => 'CAD', 'chf' => 'CHF'], 'size' => 'col-3'],
				'position'    => ['label' => $this->lang('Position'), 'value' => $pack['position'] ?? 0,      'type'  => 'number', 'size' => 'col-3'],
				'active'      => ['type' => 'checkbox', 'checked' => ['on' => $pack === NULL ? TRUE : !empty($pack['active'])], 'values' => ['on' => $this->lang('Actif')]],
			])
			->add_submit($this->lang('Enregistrer'))
			->save();

		if ($form->is_valid($post))
		{
			$data = [
				'label'       => $post['label'],
				'kind'        => $post['kind'] === 'vip' ? 'vip' : 'points',
				'units'       => max(0, (int)$post['units']),
				'price_cents' => max(0, (int)$post['price_cents']),
				'currency'    => strtolower($post['currency']),
				'position'    => (int)$post['position'],
				'active'      => in_array('on', (array)$post['active']) ? 1 : 0,
			];

			if ($id)
			{
				$this->db->where('id', $id)->update('nf_payment_packs', $data);
			}
			else
			{
				$this->db->insert('nf_payment_packs', $data);
			}

			notify($this->lang($id ? 'Pack modifié' : 'Pack créé'));
			redirect('admin/payments');
		}

		return $this->admin_card('fas fa-box', $id ? $this->lang('Modifier le pack') : $this->lang('Nouveau pack'), $form->display());
	}

	public function _pack_delete($id)
	{
		$this->check_csrf('admin/payments');

		$this->db->where('id', (int)$id)->delete('nf_payment_packs');
		notify($this->lang('Pack supprimé'));
		redirect('admin/payments');
	}
}
