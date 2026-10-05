<?php
declare(strict_types=1);
namespace NF\Widgets\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->recent($config);
	}

	public function recent($config = [])
	{
		$count = max(1, min(20, (int)($config['count'] ?? 3)));

		$msgs = NeoFrag()->db	->select('g.id', 'g.name', 'g.message', 'UNIX_TIMESTAMP(g.created_at) AS ts', 'u.id AS user_id', 'u.username')
								->from('nf_guestbook g')
								->join('nf_user u', 'g.user_id = u.id', 'LEFT')
								->where('g.status', 'approved')
								->order_by('g.created_at DESC')
								->limit($count)
								->get();

		$body = '';
		if (empty($msgs))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun message').'</small></div>';
		}
		else
		{
			foreach ($msgs as $m)
			{
				$author = $m['user_id'] && $m['username']
					? nf_texte($m['username'])
					: nf_texte($m['name']);
				$body .= '<div class="mb-2"><strong>'.$author.'</strong> <small class="text-muted">'.timetostr('j M', $m['ts']).'</small>';
				$body .= '<br><small>'.nf_texte($m['message'], 80).(mb_strlen($m['message']) > 80 ? '…' : '').'</small></div>';
			}
		}

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Livre d\'or'), 'far fa-comment-dots')
					->body($body)
					->footer('<a href="'.url('guestbook').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tous les messages').'</a>', 'right');
	}
}
