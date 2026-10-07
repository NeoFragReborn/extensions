<?php
declare(strict_types=1);
namespace NF\Modules\Bugtracker\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Bugtracker\Bugtracker;

class Index extends Controller_Module
{
	public function index($tickets, $type = '')
	{
		$this->title($this->lang('Tickets'))->icon('fas fa-bug')->breadcrumb();

		$body  = '<div class="d-flex flex-wrap align-items-center gap-2 mb-3">';
		$types = ['' => $this->lang('Tous'), 'bug' => $this->lang('Bug'), 'feature' => $this->lang('Demande de feature'), 'question' => $this->lang('Question'), 'other' => $this->lang('Autre')];

		foreach ($types as $code => $libelle)
		{
			$body .= '<a class="btn btn-sm '.($code === $type ? 'btn-primary' : 'btn-outline-secondary').'" href="'.url('bugtracker').($code !== '' ? '?type='.$code : '').'">'.$libelle.'</a>';
		}

		if ($this->user())
		{
			$body .= '<a class="btn btn-primary ms-auto" href="'.url('bugtracker/new').($type !== '' ? '?type='.$type : '').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau ticket').'</a>';
		}

		$body .= '</div>';

		$body .= '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>#</th><th>'.$this->lang('Titre').'</th><th>'.$this->lang('Type').'</th><th>'.$this->lang('Priorité').'</th><th>'.$this->lang('Statut').'</th><th>'.$this->lang('Auteur').'</th><th>'.$this->lang('Date').'</th></tr></thead><tbody>';
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
					.'<td><a href="'.url('bugtracker/'.$t['id'].'/'.url_title($t['title'])).'">'.nf_texte($t['title']).'</a></td>'
					.'<td>'.Bugtracker::type_label($t['type']).'</td>'
					.'<td>'.Bugtracker::priority_label($t['priority']).'</td>'
					.'<td>'.Bugtracker::status_label($t['status']).'</td>'
					.'<td>'.($t['reporter_id'] ? $this->user->link($t['reporter_id'], $t['reporter']) : '<i class="text-muted">'.$this->lang('Anonyme').'</i>').'</td>'
					.'<td><small>'.nf_date($t['ts']).'</small></td>'
					.'</tr>';
			}
		}
		$body .= '</tbody></table></div>';

		return $this->panel()->title($this->lang('Tickets'), 'fas fa-bug')->body($body);
	}

	public function _new()
	{
		$this->title($this->lang('Nouveau ticket'))->icon('fas fa-plus')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'rules' => 'required'],
				'type'        => ['label' => $this->lang('Type'), 'type' => 'select', 'values' => ['bug' => $this->lang('Bug'), 'feature' => $this->lang('Demande de feature'), 'question' => $this->lang('Question'), 'other' => $this->lang('Autre')], 'value' => in_array($_GET['type'] ?? '', Bugtracker::TYPES, TRUE) ? (string) $_GET['type'] : 'bug', 'rules' => 'required'],
				'priority'    => ['label' => $this->lang('Priorité'), 'type' => 'select', 'values' => ['low' => $this->lang('Faible'), 'normal' => $this->lang('Normale'), 'high' => $this->lang('Haute'), 'critical' => $this->lang('Critique')], 'value' => 'normal', 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description détaillée'), 'type' => 'textarea', 'rules' => 'required',
				                   'description' => $this->lang('Décris le problème, les étapes pour reproduire, le comportement attendu vs observé.')]
			 ])
			 ->add_submit($this->lang('Créer le ticket'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$ticket_id = $this->_modele()->creer_ticket((string) $post['title'], (string) $post['description'], (string) $post['type'], (string) $post['priority'], (int) $this->user->id);

			notify($this->lang('Ticket créé.'));
			redirect('bugtracker/'.$ticket_id.'/'.url_title($post['title']));
		}

		// « Déjà signalé ? » : js/similaires.js place cette boîte sous le titre et la remplit pendant la frappe.
		$this->js('similaires');

		$similaires = '<div id="bt-similaires" class="alert alert-warning mt-2" hidden'
			.' data-adresse="'.nf_texte(url('ajax/bugtracker/similaires')).'"'
			.' data-titre="'.nf_texte($this->lang('Déjà signalé ?')).'"'
			.' data-aide="'.nf_texte($this->lang('Ces tickets ouverts ressemblent au vôtre : s’il s’agit du même sujet, ajoutez-y plutôt un commentaire.')).'"></div>';

		return $this->row($this->col($this->panel()->heading()->body($this->form()->display().$similaires))->size('col-12'));
	}

	public function _show($ticket, $comments)
	{
		// Un ticket n'a pas de langue à lui : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this->title('#'.$ticket['id'].' — '.$ticket['title'])->icon('fas fa-bug')->breadcrumb();

		$body = '';

		if ($ticket['status'] === 'duplicate' && !empty($ticket['duplicate_of']) && ($origine = NeoFrag()->db->select('id', 'title')->from('nf_bug_tickets')->where('id', (int) $ticket['duplicate_of'])->row()))
		{
			$lien  = '<a href="'.url('bugtracker/'.$origine['id'].'/'.url_title($origine['title'])).'">#'.(int) $origine['id'].' — '.nf_texte($origine['title']).'</a>';
			$body .= '<div class="alert alert-info">'.icon('fas fa-clone').' '.$this->lang('Ce ticket est un doublon de %s : la suite se passe là-bas.', $lien).'</div>';
		}

		$body .= '<div class="mb-3">';
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
		$body .= '<div class="card mb-3"><div class="card-body">'.nl2br(nf_texte($ticket['description'])).'</div></div>';
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
				$author = $c['user_id'] ? $this->user->link($c['user_id'], $c['username']) : (!empty($c['author_name']) ? icon('fab fa-discord').' '.nf_texte($c['author_name']) : '<i>'.$this->lang('Anonyme').'</i>');
				$body .= '<div class="card mb-2"><div class="card-body py-2">';
				$body .= '<div class="d-flex justify-content-between mb-1"><strong>'.$author.'</strong><small class="text-muted">'.nf_date_heure($c['ts']).'</small></div>';
				$body .= '<div>'.nl2br(nf_texte($c['content'])).'</div>';
				$body .= '</div></div>';
			}
		}

		// Form pour commenter (loggés seulement)
		if ($this->user())
		{
			$body .= '<form method="post" action="'.url('bugtracker/'.$ticket['id'].'/'.url_title($ticket['title']).'/comment').'" class="mt-3">';
			$body .= '<div class="nf-field"><label>'.$this->lang('Ajouter un commentaire').'</label>';
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
			$this->_modele()->commenter((int) $ticket['id'], (int) $this->user->id, $content);
			notify($this->lang('Commentaire ajouté.'));
		}
		redirect('bugtracker/'.$ticket['id'].'/'.url_title($ticket['title']));
	}

	/** Le modèle du module, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Bugtracker\Models\Bugtracker
	{
		$modele = $this->model('bugtracker');

		if (!$modele instanceof \NF\Modules\Bugtracker\Models\Bugtracker)
		{
			throw new \LogicException('modèle du Bugtracker introuvable');
		}

		return $modele;
	}
}
