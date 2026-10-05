<?php
declare(strict_types=1);
namespace NF\Modules\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($messages)
	{
		$this	->title($this->lang('Livre d\'or'))
				->icon('far fa-comment-dots')
				->breadcrumb();

		$user = $this->user();

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

		if ($this->form()->is_valid($post))
		{
			$check = $rate_limit->check('guestbook:ip:'.$ip);

			if (!$check['allowed'])
			{
				$this->form()->error($this->lang('Trop de messages récents. Réessaye dans %d minute(s).', ceil($check['retry_after'] / 60)));
			}
			else
			{
				$rate_limit->hit('guestbook:ip:'.$ip, 3, 600, 1800);

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
		$body = '<div class="mb-4">'.$this->form()->display().'</div>';
		$body .= '<hr>';

		if (empty($messages))
		{
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
					.'<small class="text-muted">'.timetostr('j M Y H:i', $m['ts']).'</small>'
					.'</div>'
					.'<p class="mb-0">'.nl2br(nf_texte($m['message'])).'</p>'
					.'</div>'
					.'</div>';
			}
		}

		return $this->panel()->title($this->lang('Livre d\'or'), 'far fa-comment-dots')->body($body);
	}
}
