<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Bugtracker\Models;

use NF\NeoFrag\Loadables\Model;

class Bugtracker extends Model
{
	/*
	 * Les écritures du Bugtracker, en un seul endroit (2026-10-01) : les pages du module, l'API et donc
	 * le bot Discord passent par ici, et chaque changement émet son événement (`bugtracker.*`) — le bot
	 * tient ainsi à jour le fil Discord de chaque ticket (point 7). Jusque-là, chaque page
	 * écrivait elle-même dans la base, et rien d'autre n'en savait rien.
	 */

	/** Les statuts après lesquels un ticket est clos (son fil Discord s'archive). */
	const STATUTS_CLOS = ['resolved', 'closed', 'wont_fix', 'duplicate'];

	/** Les autres : le ticket est encore ouvert. */
	const STATUTS_OUVERTS = ['open', 'in_progress'];

	/**
	 * Un ticket, prêt à servir : ses champs, son auteur, le nombre de ses commentaires.
	 *
	 * @return array<string, mixed>|null
	 */
	public function ticket(int $id): ?array
	{
		$ticket = $this->db	->select('t.id', 't.title', 't.description', 't.type', 't.priority', 't.status', 't.duplicate_of', 't.user_id', 't.created_at', 't.updated_at', 'u.username')
							->from('nf_bug_tickets t')
							->join('nf_user u', 'u.id = t.user_id AND u.deleted = "0"', 'LEFT')
							->where('t.id', $id)
							->row();

		return is_array($ticket) && $ticket ? $ticket : NULL;
	}

	/**
	 * Les numéros des tickets qui suivent `$apres`, dans l'ordre, au plus `$limite` ; les seuls
	 * tickets encore ouverts si `$ouverts`.
	 *
	 * @return list<int>
	 */
	public function numeros(int $apres, int $limite, bool $ouverts): array
	{
		$requete = $this->db->select('id')->from('nf_bug_tickets')->where('id >', $apres);

		if ($ouverts)
		{
			$requete->where('status', self::STATUTS_OUVERTS);
		}

		// Une seule colonne lue : `get()` rend directement les valeurs, pas des lignes.
		return array_map('intval', (array) $requete->order_by('id')->limit($limite)->get());
	}

	/** @return array<string, mixed>|null */
	public function commentaire(int $id): ?array
	{
		$commentaire = $this->db	->select('c.id', 'c.ticket_id', 'c.user_id', 'c.content', 'c.is_status_change', 'c.author_provider', 'c.author_external_id', 'c.author_name', 'c.created_at', 'u.username')
									->from('nf_bug_comments c')
									->join('nf_user u', 'u.id = c.user_id AND u.deleted = "0"', 'LEFT')
									->where('c.id', $id)
									->row();

		return is_array($commentaire) && $commentaire ? $commentaire : NULL;
	}

	/** Ouvre un ticket ; rend son numéro. */
	public function creer_ticket(string $titre, string $description, string $type, string $priorite, ?int $user_id): int
	{
		$id = (int) $this->db->insert('nf_bug_tickets', [
			'title'       => mb_substr($titre, 0, 200),
			'description' => $description,
			'type'        => in_array($type, \NF\Modules\Bugtracker\Bugtracker::TYPES, TRUE) ? $type : 'bug',
			'priority'    => in_array($priorite, ['low', 'normal', 'high', 'critical'], TRUE) ? $priorite : 'normal',
			'status'      => 'open',
			'user_id'     => $user_id,
		]);

		$this->events->fire('bugtracker.ticket.created', ['ticket_id' => $id, 'user_id' => $user_id, 'type' => $type]);

		return $id;
	}

	/**
	 * Modifie un ticket (administration). Rend les champs qui ont vraiment changé ; l'événement
	 * `bugtracker.ticket.updated` les nomme.
	 *
	 * @param array<string, mixed> $champs
	 * @return list<string>
	 */
	public function modifier_ticket(int $id, array $champs): array
	{
		$avant = $this->ticket($id);

		if (!$avant)
		{
			return [];
		}

		$changes = array_values(array_filter(array_keys($champs), static fn (string $c): bool => array_key_exists($c, $avant) && (string) $avant[$c] !== (string) $champs[$c]));

		$this->db->where('id', $id)->update('nf_bug_tickets', $champs);

		if ($changes)
		{
			$apres = (array) $this->ticket($id);

			$this->events->fire('bugtracker.ticket.updated', ['ticket_id' => $id, 'status' => (string) $apres['status'], 'type' => (string) $apres['type'], 'fields' => $changes]);
		}

		return $changes;
	}

	public function supprimer_ticket(int $id): void
	{
		$this->db->where('id', $id)->delete('nf_bug_tickets');
		$this->db->where('ticket_id', $id)->delete('nf_bug_comments');

		$this->events->fire('bugtracker.ticket.deleted', ['ticket_id' => $id]);
	}

	/**
	 * Commente un ticket : au nom d'un membre, ou d'un auteur venu d'ailleurs (un compte Discord qui
	 * n'a pas lié son compte : son fournisseur, son identifiant, son pseudo). Rend le numéro du commentaire.
	 *
	 * @param array{provider: string, external_id: string, name: string}|null $externe
	 */
	public function commenter(int $ticket_id, ?int $user_id, string $contenu, ?array $externe = NULL): int
	{
		$id = (int) $this->db->insert('nf_bug_comments', [
			'ticket_id'          => $ticket_id,
			'user_id'            => $user_id,
			'content'            => $contenu,
			'author_provider'    => $externe ? mb_substr($externe['provider'], 0, 20) : NULL,
			'author_external_id' => $externe ? mb_substr($externe['external_id'], 0, 64) : NULL,
			'author_name'        => $externe ? mb_substr($externe['name'], 0, 100) : NULL,
		]);

		$this->db->execute('UPDATE nf_bug_tickets SET updated_at = NOW() WHERE id = '.$ticket_id);
		$this->events->fire('bugtracker.comment.created', ['comment_id' => $id, 'ticket_id' => $ticket_id, 'user_id' => $user_id]);

		return $id;
	}

	public function modifier_commentaire(int $id, string $contenu): void
	{
		$commentaire = $this->commentaire($id);

		if ($commentaire && (string) $commentaire['content'] !== $contenu)
		{
			$this->db->where('id', $id)->update('nf_bug_comments', ['content' => $contenu]);
			$this->events->fire('bugtracker.comment.edited', ['comment_id' => $id, 'ticket_id' => (int) $commentaire['ticket_id']]);
		}
	}

	public function supprimer_commentaire(int $id): void
	{
		$commentaire = $this->commentaire($id);

		if ($commentaire)
		{
			$this->db->where('id', $id)->delete('nf_bug_comments');
			$this->events->fire('bugtracker.comment.deleted', ['comment_id' => $id, 'ticket_id' => (int) $commentaire['ticket_id']]);
		}
	}
}
