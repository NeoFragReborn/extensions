<?php
declare(strict_types=1);
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$tickets = NeoFrag()->db	->select('t.id', 't.title', 't.type', 't.priority', 't.status', 'UNIX_TIMESTAMP(t.created_at) AS ts', 'u.id AS reporter_id', 'u.username AS reporter')
									->from('nf_bug_tickets t')
									->join('nf_user u', 't.user_id = u.id', 'LEFT')
									->order_by('FIELD(t.status, "open","in_progress","resolved","closed","wont_fix") ASC, t.created_at DESC')
									->get();
		return [$tickets];
	}

	public function _new()
	{
		$this->error->unconnected();
		return [];
	}

	public function _show($ticket_id, $title)
	{
		$ticket = NeoFrag()->db	->select('t.*', 'u.username AS reporter', 'u.id AS reporter_id', 'a.username AS assignee', 'a.id AS assignee_id', 'UNIX_TIMESTAMP(t.created_at) AS created_ts', 'UNIX_TIMESTAMP(t.updated_at) AS updated_ts')
								->from('nf_bug_tickets t')
								->join('nf_user u', 't.user_id = u.id', 'LEFT')
								->join('nf_user a', 't.assigned_to = a.id', 'LEFT')
								->where('t.id', $ticket_id)
								->row();
		if (empty($ticket)) return;

		$comments = NeoFrag()->db	->select('c.*', 'u.username', 'u.id AS user_id', 'UNIX_TIMESTAMP(c.created_at) AS ts')
									->from('nf_bug_comments c')
									->join('nf_user u', 'c.user_id = u.id', 'LEFT')
									->where('c.ticket_id', $ticket_id)
									->order_by('c.created_at ASC')
									->get();

		return [$ticket, $comments];
	}

	public function _comment($ticket_id, $title)
	{
		$this->error->unconnected();
		$ticket = NeoFrag()->db->select('id', 'title')->from('nf_bug_tickets')->where('id', $ticket_id)->row();
		return $ticket ? [$ticket] : NULL;
	}
}
