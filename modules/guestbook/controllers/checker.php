<?php
declare(strict_types=1);
namespace NF\Modules\Guestbook\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$messages = NeoFrag()->db	->select('g.id', 'g.name', 'g.message', 'UNIX_TIMESTAMP(g.created_at) AS ts', 'g.user_id', 'u.username')
									->from('nf_guestbook g')
									->join('nf_user u', 'g.user_id = u.id', 'LEFT')
									->where('g.status', 'approved')
									->order_by('g.created_at DESC')
									->get();

		return [$messages];
	}
}
