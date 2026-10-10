<?php
declare(strict_types=1);
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	/**
	 * `?type=bug|feature|question|other` filtre la liste (2026-10-01) : les forums « Signaler un bug »
	 * et « Suggestions » de la vitrine mènent ici, sur les tickets déjà ouverts du même type, avant le
	 * formulaire — on voit ce qui est signalé avant de le signaler une seconde fois.
	 */
	public function index()
	{
		$type = in_array($_GET['type'] ?? '', \NF\Modules\Bugtracker\Bugtracker::TYPES, TRUE) ? (string) $_GET['type'] : '';

		NeoFrag()->db	->select('t.id', 't.title', 't.type', 't.priority', 't.status', 'UNIX_TIMESTAMP(t.created_at) AS ts', 'u.id AS reporter_id', 'u.username AS reporter')
						->from('nf_bug_tickets t')
						->join('nf_user u', 't.user_id = u.id', 'LEFT')
						->order_by('FIELD(t.status, "open","in_progress","resolved","closed","wont_fix","duplicate") ASC, t.created_at DESC');

		if ($type !== '')
		{
			NeoFrag()->db->where('t.type', $type);
		}

		// Ni les tickets d'un membre sous shadow ban (audit du 2026-10-09).
		if ($sans_masques = NeoFrag()->moderation->condition_sans_masques('t.user_id'))
		{
			NeoFrag()->db->where($sans_masques);
		}

		return [NeoFrag()->db->get(), $type];
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

		// Ouvert par un membre sous shadow ban : introuvable pour les autres ; ses commentaires ne se montrent pas
		// (audit du 2026-10-09).
		$masques = NeoFrag()->moderation->auteurs_masques();

		if ($ticket['user_id'] && in_array((int) $ticket['user_id'], $masques, TRUE))
		{
			return;
		}

		// Le titre de l'adresse n'est pas le bon : 301 vers la bonne (elle répondait 200 à n'importe lequel).
		nf_bon_titre((string) $title, (string) $ticket['title'], 'bugtracker/'.(int) $ticket_id);

		$comments = NeoFrag()->moderation->sans_masques((array) NeoFrag()->db	->select('c.*', 'u.username', 'u.id AS user_id', 'UNIX_TIMESTAMP(c.created_at) AS ts')
									->from('nf_bug_comments c')
									->join('nf_user u', 'c.user_id = u.id', 'LEFT')
									->where('c.ticket_id', $ticket_id)
									->order_by('c.created_at ASC')
									->get());

		return [$ticket, $comments];
	}

	public function _comment($ticket_id, $title)
	{
		$this->error->unconnected();
		$ticket = NeoFrag()->db->select('id', 'title')->from('nf_bug_tickets')->where('id', $ticket_id)->row();
		return $ticket ? [$ticket] : NULL;
	}
}
