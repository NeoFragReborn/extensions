<?php
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Bugtracker\Bugtracker;

class Index extends Controller_Module
{
	public function index($tickets)
	{
		$this->title($this->lang('Tickets'))->icon('fas fa-bug')->breadcrumb();

		$body = '';
		if ($this->user())
		{
			$body .= '<a class="btn btn-primary mb-3" href="'.url('bugtracker/new').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau ticket').'</a>';
		}

		$body .= '<table class="table table-sm"><thead><tr><th>#</th><th>'.$this->lang('Titre').'</th><th>'.$this->lang('Type').'</th><th>'.$this->lang('Priorité').'</th><th>'.$this->lang('Statut').'</th><th>'.$this->lang('Auteur').'</th><th>'.$this->lang('Date').'</th></tr></thead><tbody>';
		if (empty($tickets))
		{
			$body .= '<tr><td colspan="7" class="text-center text-muted">'.$this->lang('Aucun ticket').'</td></tr>';
		}
		else
		{
			foreach ($tickets as $t)
			{
				$body .= '<tr>'
					.'<td>#'.(int)$t['id'].'</td>'
					.'<td><a href="'.url('bugtracker/'.$t['id'].'/'.url_title($t['title'])).'">'.htmlspecialchars($t['title']).'</a></td>'
					.'<td>'.Bugtracker::type_label($t['type']).'</td>'
					.'<td>'.Bugtracker::priority_label($t['priority']).'</td>'
					.'<td>'.Bugtracker::status_label($t['status']).'</td>'
					.'<td>'.($t['reporter_id'] ? $this->user->link($t['reporter_id'], $t['reporter']) : '<i class="text-muted">'.$this->lang('Anonyme').'</i>').'</td>'
					.'<td><small>'.date('Y-m-d', $t['ts']).'</small></td>'
					.'</tr>';
			}
		}
		$body .= '</tbody></table>';

		return $this->panel()->title($this->lang('Tickets'), 'fas fa-bug')->body($body);
	}

	public function _new()
	{
		$this->title($this->lang('Nouveau ticket'))->icon('fas fa-plus')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'rules' => 'required'],
				'type'        => ['label' => $this->lang('Type'), 'type' => 'select', 'values' => ['bug' => $this->lang('Bug'), 'feature' => $this->lang('Demande de feature'), 'question' => $this->lang('Question'), 'other' => $this->lang('Autre')], 'value' => 'bug', 'rules' => 'required'],
				'priority'    => ['label' => $this->lang('Priorité'), 'type' => 'select', 'values' => ['low' => $this->lang('Faible'), 'normal' => $this->lang('Normale'), 'high' => $this->lang('Haute'), 'critical' => $this->lang('Critique')], 'value' => 'normal', 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description détaillée'), 'type' => 'textarea', 'rules' => 'required',
				                   'description' => $this->lang('Décris le problème, les étapes pour reproduire, le comportement attendu vs observé.')]
			 ])
			 ->add_submit($this->lang('Créer le ticket'));

		if ($this->form()->is_valid($post))
		{
			NeoFrag()->db->insert('nf_bug_tickets', [
				'title'       => $post['title'],
				'description' => $post['description'],
				'type'        => $post['type'],
				'priority'    => $post['priority'],
				'status'      => 'open',
				'user_id'     => $this->user->id
			]);
			$ticket_id = (int)NeoFrag()->db->driver()->insert_id();

			notify($this->lang('Ticket créé.'));
			redirect('bugtracker/'.$ticket_id.'/'.url_title($post['title']));
		}

		return $this->row($this->col($this->panel()->heading()->body($this->form()->display()))->size('col-12'));
	}

	public function _show($ticket, $comments)
	{
		$this->title('#'.$ticket['id'].' — '.$ticket['title'])->icon('fas fa-bug')->breadcrumb();

		$body = '<div class="mb-3">';
		$body .= '<div class="d-flex flex-wrap gap-2 align-items-center mb-2">';
		$body .= Bugtracker::status_label($ticket['status']).' ';
		$body .= Bugtracker::type_label($ticket['type']).' ';
		$body .= Bugtracker::priority_label($ticket['priority']);
		$body .= '</div>';
		$body .= '<div class="text-muted small mb-2">';
		$author_link = $ticket['reporter_id'] ? $this->user->link($ticket['reporter_id'], $ticket['reporter']) : $this->lang('Anonyme');
		$body .= $this->lang('Ouvert par %s', $author_link);
		$body .= ' '.timetostr('j M Y H:i', $ticket['created_ts']);
		if ($ticket['assignee_id'])
		{
			$body .= ' — '.$this->lang('Assigné à %s', $this->user->link($ticket['assignee_id'], $ticket['assignee']));
		}
		$body .= '</div>';
		$body .= '<div class="card mb-3"><div class="card-body">'.nl2br(htmlspecialchars($ticket['description'])).'</div></div>';
		$body .= '</div>';

		// Comments
		$body .= '<h3 class="h5">'.$this->lang('Commentaires (%d)', count($comments)).'</h3>';
		if (empty($comments))
		{
			$body .= '<div class="text-muted text-center py-3">'.$this->lang('Aucun commentaire pour le moment.').'</div>';
		}
		else
		{
			foreach ($comments as $c)
			{
				$author = $c['user_id'] ? $this->user->link($c['user_id'], $c['username']) : '<i>'.$this->lang('Anonyme').'</i>';
				$body .= '<div class="card mb-2"><div class="card-body py-2">';
				$body .= '<div class="d-flex justify-content-between mb-1"><strong>'.$author.'</strong><small class="text-muted">'.date('Y-m-d H:i', $c['ts']).'</small></div>';
				$body .= '<div>'.nl2br(htmlspecialchars($c['content'])).'</div>';
				$body .= '</div></div>';
			}
		}

		// Form pour commenter (loggés seulement)
		if ($this->user())
		{
			$body .= '<form method="post" action="'.url('bugtracker/'.$ticket['id'].'/'.url_title($ticket['title']).'/comment').'" class="mt-3">';
			$body .= '<div class="form-group"><label>'.$this->lang('Ajouter un commentaire').'</label>';
			$body .= '<textarea name="content" class="form-control" rows="3" required></textarea></div>';
			$body .= '<button type="submit" class="btn btn-primary"><i class="fas fa-comment"></i> '.$this->lang('Commenter').'</button>';
			$body .= '</form>';
		}
		else
		{
			$body .= '<div class="alert alert-info mt-3">'.$this->lang('Connecte-toi pour commenter.').'</div>';
		}

		return $this->panel()->title('Ticket #'.$ticket['id'], 'fas fa-bug')->body($body);
	}

	public function _comment($ticket)
	{
		$content = trim($_POST['content'] ?? '');
		if ($content !== '')
		{
			NeoFrag()->db->insert('nf_bug_comments', [
				'ticket_id' => $ticket['id'],
				'user_id'   => $this->user->id,
				'content'   => $content
			]);
			NeoFrag()->db->execute('UPDATE nf_bug_tickets SET updated_at = NOW() WHERE id = '.(int)$ticket['id']);
			notify($this->lang('Commentaire ajouté.'));
		}
		redirect('bugtracker/'.$ticket['id'].'/'.url_title($ticket['title']));
	}
}
