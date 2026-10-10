<?php
declare(strict_types=1);
namespace NF\Modules\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	/** Le bouton « Signaler » de la modération, s'il est là. */
	private function moderation_signaler(string $type, int $id, string $adresse, ?int $auteur): string
	{
		return ($moderation = $this->module('moderation')) instanceof \NF\Modules\Moderation\Moderation ? $moderation->report_button($type, $id, $adresse, NULL, $auteur) : '';
	}

	public function index($messages)
	{
		$this	->title($this->lang('Livre d\'or'))
				->icon('far fa-comment-dots')
				->breadcrumb();

		$user = $this->user();

		// Une sanction qui ferme le livre d'or (son bannissement, celui du site) : l'avis à la place du formulaire.
		$bloque = $user ? $this->moderation->is_blocked_for((int) $user->id, 'guestbook.write') : NULL;

		// Form
		$rate_limit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$ip = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();

		$this->form()
			 ->add_rules([
				'name' => [
					'label' => $this->lang('Nom (visible)'),
					'type'  => 'text',
					'value' => $user ? $user->username : '',
					'rules' => 'required'
				],
				'message' => [
					'label' => $this->lang('Message'),
					'type'  => 'textarea',
					'rules' => 'required'
				]
			 ])
			 ->add_submit($this->lang('Signer le livre d\'or'), 'fas fa-pen');

		if (!$bloque && $this->form()->is_valid($post))
		{
			$check = $rate_limit->check('guestbook:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip($ip));

			if ($refus = $this->moderation->lien_refuse($user ? (int) $user->id : 0, $post['name'], $post['message']))
			{
				$this->form()->error($refus['message']);
			}
			else if (!$check['allowed'])
			{
				$this->form()->error($this->lang('Trop de messages récents. Réessaye dans %d minute(s).', ceil($check['retry_after'] / 60)));
			}
			else
			{
				$rate_limit->hit('guestbook:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip($ip), 3, 600, 1800);

				NeoFrag()->db->insert('nf_guestbook', [
					'user_id'    => $user ? $user->id : NULL,
					'name'       => trim($post['name']),
					'message'    => trim($post['message']),
					'ip_address' => $ip,
					'status'     => 'pending'
				]);

				notify($this->lang('Merci ! Ton message est en attente de modération.'));
				redirect('guestbook');
			}
		}

		// Render messages list
		$body = '<div class="mb-4">'.($bloque ? $this->moderation->avis($bloque) : $this->form()->display()).'</div>';
		$body .= '<hr>';

		if (empty($messages))
		{
			// Une page vide, que les moteurs n'indexent pas (et que le plan du site tait).
			$this->output->data->set('module', 'robots', 'noindex, follow');

			$body .= '<div class="alert alert-info text-center">'.$this->lang('Aucun message pour le moment. Sois le premier !').'</div>';
		}
		else
		{
			foreach ($messages as $m)
			{
				$display_name = $m['user_id'] && $m['username']
					? $this->user->link($m['user_id'], $m['username'])
					: '<strong>'.nf_texte($m['name']).'</strong>';

				$body .= '<div class="card mb-2">'
					.'<div class="card-body">'
					.'<div class="d-flex justify-content-between mb-1">'
					.'<span>'.$display_name.'</span>'
					.'<small class="text-muted">'.timetostr('j M Y H:i', $m['ts'])
					// Signaler un message (2026-10-09 : le livre d'or n'avait pas de bouton).
					.$this->moderation_signaler('guestbook', (int) $m['id'], url('guestbook'), $m['user_id'] ? (int) $m['user_id'] : NULL).'</small>'
					.'</div>'
					.'<p class="mb-0">'.nl2br(nf_texte($m['message'])).'</p>'
					.'</div>'
					.'</div>';
			}
		}

		return $this->panel()->title($this->lang('Livre d\'or'), 'far fa-comment-dots')->body($body);
	}
}
