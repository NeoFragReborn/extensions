<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Webradio\Lib\Schedule;

class Admin extends Controller_Module
{
	/** Les jours de la grille : leurs NOMS viennent de `Index::nom_jour()`, que le produit localise. */
	const JOURS = [1, 2, 3, 4, 5, 6, 7];

	public function index($station, $creneaux, $filtres)
	{
		$this->title($this->lang('Webradio'))->icon('fas fa-broadcast-tower');

		// ── L'état de la station, en tête : c'est ce qu'on vient vérifier ─────
		$etat = '<table class="table" style="margin:0;"><tbody>';
		$etat .= '<tr><th style="width:11rem;">'.$this->lang('Nom de la station').'</th><td>'
			.($station['name'] !== '' ? htmlspecialchars((string) ($station['name'])) : '<span class="text-muted">'.$this->lang('non renseigné').'</span>').'</td></tr>';

		$etat .= '<tr><th>'.$this->lang('Flux').'</th><td>';

		if ($station['stream'] === '')
		{
			$etat .= '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Aucun flux configuré : la page publique n\'affiche pas de lecteur.').'</span>';
		}
		else
		{
			$etat .= '<code>'.htmlspecialchars((string) ($station['stream'])).'</code>';

			if (Schedule::contenu_mixte($station['stream']))
			{
				$etat .= '<br /><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> '
					.$this->lang('Ce flux est en http : les navigateurs le refuseront sur un site en https.').'</span>';
			}
		}

		$etat .= '</td></tr>';

		// Ce que la politique de sécurité autorise réellement : sans cette ligne, un administrateur
		// dont le flux ne démarre pas n'a aucun moyen de savoir si c'est elle qui le bloque.
		$etat .= '<tr><th>'.$this->lang('Origine autorisée').'</th><td>'
			.($station['origin'] !== ''
				? '<code>'.htmlspecialchars((string) ($station['origin'])).'</code> <span class="text-muted">'.$this->lang('ajoutée à la politique de sécurité du site').'</span>'
				: '<span class="text-muted">'.$this->lang('aucune — le site ne déclare aucune origine tierce pour les médias').'</span>')
			.'</td></tr>';

		$etat .= '</tbody></table>';

		// La configuration s'ouvre dans la modale commune à tous les addons — celle du bouton
		// « Configuration » de la barre d'administration —, et non par une route inventée
		// (`admin/settings/addons/module/webradio` rendait 404 ; trouvé par check-liens en CI).
		$etat_actions = (string) $this->button($this->lang('Configurer'), 'fas fa-cog', 'primary')
			->compact()
			->modal_ajax('admin/addons/settings/'.$this->module('webradio')->__addon->id.'/webradio');

		// ── La grille ─────────────────────────────────────────────────────────
		$publies    = (int) $filtres['published'];
		$brouillons = (int) $filtres['drafts'];

		if (empty($creneaux))
		{
			$corps = !empty($filtres['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune émission ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-microphone', $this->lang('Aucune émission.'));
		}
		else
		{
			$corps = '<table class="table table-hover"><thead><tr>'
				.'<th>'.$this->lang('Jour').'</th>'
				.'<th>'.$this->lang('Horaire').'</th>'
				.'<th>'.$this->lang('Titre').'</th>'
				.'<th>'.$this->lang('Animateur').'</th>'
				.'<th class="text-end"></th></tr></thead><tbody>';

			foreach ($creneaux as $c)
			{
				$slug   = url_title($c['title']);
				$publie = !empty($c['published']);
				$jour   = Schedule::jour($c['day']);
				$nuit   = Schedule::heure($c['start_time']) > Schedule::heure($c['end_time']) && Schedule::heure($c['end_time']) !== '';

				$corps .= '<tr>'
					.'<td>'.htmlspecialchars((string) (Index::nom_jour($jour))).'</td>'
					.'<td style="white-space:nowrap;">'.htmlspecialchars((string) ($c['start_time'].' – '.$c['end_time']))
					// Un créneau qui enjambe minuit se lit mal : on le dit, plutôt que de laisser
					// croire à une faute de saisie.
					.($nuit ? ' <span class="badge text-bg-light" title="'.htmlspecialchars((string) ($this->lang('Ce créneau se termine le lendemain.')), ENT_QUOTES).'"><i class="fas fa-moon"></i></span>' : '')
					.'</td>'
					.'<td><strong>'.htmlspecialchars((string) ($c['title'])).'</strong>'
					.(!$publie ? ' <span class="badge text-bg-light">'.$this->lang('Brouillon').'</span>' : '')
					.'</td>'
					.'<td class="text-muted">'.htmlspecialchars((string) ($c['host'])).'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/webradio/s/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/webradio/s/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette émission ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$corps .= '</tbody></table>';
		}

		// ── Barre de recherche et de filtre (GET, préservé par la pagination) ──
		$action = url($this->module->pagination->get_url());
		$barre  = '<form method="get" action="'.$action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$barre .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filtres['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher une émission, un animateur…')), ENT_QUOTES).'" style="max-width:260px;">';
		$barre .= '<select name="day" class="form-select form-select-sm" style="width:auto;">';
		$barre .= '<option value="0">'.$this->lang('Tous les jours').'</option>';

		foreach (self::JOURS as $numero)
		{
			$barre .= '<option value="'.$numero.'"'.((int) $filtres['day'] === $numero ? ' selected' : '').'>'.htmlspecialchars((string) (Index::nom_jour($numero))).'</option>';
		}

		$barre .= '</select>';
		$barre .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';

		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiées'), 'draft' => $this->lang('Brouillons')] as $valeur => $libelle)
		{
			$barre .= '<option value="'.$valeur.'"'.($filtres['status'] === $valeur ? ' selected' : '').'>'.htmlspecialchars((string) ($libelle)).'</option>';
		}

		$barre .= '</select>';
		$barre .= $this->sort_select($filtres['sort_cols'], $filtres['sort']);
		$barre .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';

		if (!empty($filtres['active']))
		{
			$barre .= '<a href="'.$action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
			$barre .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int) $filtres['matched'], (int) $filtres['matched']).'</span>';
		}

		$barre .= '</form>';

		$pagination = (string) $this->module->pagination->get_pagination();

		if ($pagination !== '')
		{
			$pagination = '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
		}

		$corps = $barre.$corps.$pagination;

		$actions    = '<a class="btn btn-primary btn-sm" href="'.url('admin/webradio/s/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle émission').'</a>';
		$sous_titre = $publies.' '.$this->lang('publiée|publiées', $publies).($brouillons > 0 ? ' · '.$brouillons.' '.$this->lang('brouillon|brouillons', $brouillons) : '');

		return $this->admin_card('fas fa-broadcast-tower', $this->lang('La station'), $etat, '', $etat_actions)
			.$this->admin_card('fas fa-microphone', $this->lang('Les émissions'), $corps, $sous_titre, $actions);
	}

	// ── Émissions ─────────────────────────────────────────────────────────────

	public function _s_add()    { return $this->_s_form(NULL); }
	public function _s_edit($s) { return $this->_s_form($s); }

	public function _s_delete($s)
	{
		$this->check_csrf('admin/webradio');

		NeoFrag()->db->where('id', $s['id'])->delete('nf_webradio_shows');
		notify($this->lang('Émission supprimée.'));
		redirect('admin/webradio');
	}

	protected function _s_form($s)
	{
		$nouvelle = $s === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle émission') : $this->lang('Éditer l\'émission'))->icon('fas fa-microphone')->breadcrumb();

		$jours = [];
		foreach (self::JOURS as $numero) { $jours[$numero] = Index::nom_jour($numero); }

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouvelle ? '' : $s['title'], 'rules' => 'required'],
				'host'        => ['label' => $this->lang('Animateur'), 'type' => 'text', 'value' => $nouvelle ? '' : $s['host']],
				'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => $nouvelle ? '' : $s['description']],
				'day'         => ['label' => $this->lang('Jour'), 'type' => 'select', 'values' => $jours, 'value' => $nouvelle ? 1 : Schedule::jour($s['day']), 'rules' => 'required'],
				'start_time'  => ['label' => $this->lang('Début (HH:MM)'), 'type' => 'text', 'value' => $nouvelle ? '' : $s['start_time'], 'rules' => 'required'],
				'end_time'    => ['label' => $this->lang('Fin (HH:MM)'), 'type' => 'text', 'value' => $nouvelle ? '' : $s['end_time'], 'rules' => 'required'],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Émission publiée')], 'checked' => ['1' => ($nouvelle || !empty($s['published']))]]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$debut = Schedule::heure($post['start_time']);
			$fin   = Schedule::heure($post['end_time']);

			if ($debut === '' || $fin === '')
			{
				// On refuse plutôt que d'enregistrer un créneau qui ne passerait jamais à l'antenne
				// sans que rien ne l'explique.
				notify($this->lang('Horaires invalides : attendus au format HH:MM, de 00:00 à 23:59.'));
			}
			else
			{
				$donnees = [
					'title'       => $post['title'],
					'host'        => $post['host'],
					'description' => $post['description'],
					'day'         => Schedule::jour($post['day']),
					'start_time'  => $debut,
					'end_time'    => $fin,
					'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
				];

				if ($nouvelle) NeoFrag()->db->insert('nf_webradio_shows', $donnees);
				else           NeoFrag()->db->where('id', $s['id'])->update('nf_webradio_shows', $donnees);

				notify($nouvelle ? $this->lang('Émission créée.') : $this->lang('Émission modifiée.'));
				redirect('admin/webradio');
			}
		}

		$aide = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> '
			.$this->lang('Une émission peut enjamber minuit : « 22:00 → 02:00 » se termine le lendemain, et reste rattachée au jour où elle commence.')
			.'</div>';

		return $this->admin_card($nouvelle ? 'fas fa-plus' : 'fas fa-edit', $nouvelle ? $this->lang('Nouvelle émission') : $this->lang('Éditer l\'émission'), $aide.$this->form()->display());
	}
}
