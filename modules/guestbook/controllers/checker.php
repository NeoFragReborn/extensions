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
									// Ni ceux d'un membre sous shadow ban (audit du 2026-10-09).
									->where_if($sans_masques = NeoFrag()->moderation->condition_sans_masques('g.user_id'), $sans_masques)
									->order_by('g.created_at DESC')
									->get();

		return [$messages];
	}
}
