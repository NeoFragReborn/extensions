<?php
declare(strict_types=1);
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		$tickets = NeoFrag()->db	->select('t.*', 'u.username AS reporter', 'a.username AS assignee', 'UNIX_TIMESTAMP(t.created_at) AS ts', 'COUNT(c.id) AS nb_comments')
									->from('nf_bug_tickets t')
									->join('nf_user u', 't.user_id = u.id', 'LEFT')
									->join('nf_user a', 't.assigned_to = a.id', 'LEFT')
									->join('nf_bug_comments c', 't.id = c.ticket_id', 'LEFT')
									->group_by('t.id')
									->order_by('FIELD(t.status, "open","in_progress","resolved","closed","wont_fix") ASC, t.created_at DESC')
									->get();
		return [$tickets];
	}

	public function _edit($id, $title)
	{
		$t = NeoFrag()->db->select('*')->from('nf_bug_tickets')->where('id', $id)->row();
		return $t ? [$t] : NULL;
	}

	public function _delete($id, $title)
	{
		$t = NeoFrag()->db->select('id', 'title')->from('nf_bug_tickets')->where('id', $id)->row();
		return $t ? [$t] : NULL;
	}
}
