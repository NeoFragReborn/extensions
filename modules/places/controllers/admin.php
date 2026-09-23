<?php
declare(strict_types=1);
namespace NF\Modules\Places\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Places\Lib\Place;

class Admin extends Controller_Module
{
	public function index($cats, $lieux, $filtres)
	{
		$this->title($this->lang('Carte des lieux'))->icon('fas fa-map-marked-alt');

		// ── Colonne des catégories ────────────────────────────────────────────
		if (empty($cats))
		{
			$cats_corps = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		}
		else
		{
			$cats_corps = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Lieux').'</th><th class="text-end"></th></tr></thead><tbody>';

			foreach ($cats as $c)
			{
				$slug  = url_title($c['title']);
				$icone = trim((string) $c['icon']) !== '' ? $c['icon'] : 'fas fa-map-marker-alt';

				$cats_corps .= '<tr>'
					.'<td>'.icon($icone).' <strong>'.htmlspecialchars((string) ($c['title'])).'</strong></td>'
					.'<td class="text-end">'.(int) $c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/places/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/places/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette catégorie ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$cats_corps .= '</tbody></table>';
		}

		// ── Colonne des lieux ─────────────────────────────────────────────────
		$publies    = (int) $filtres['published'];
		$brouillons = (int) $filtres['drafts'];

		if (empty($lieux))
		{
			$corps = !empty($filtres['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun lieu ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-map-marker-alt', $this->lang('Aucun lieu.'));
		}
		else
		{
			$corps = '<table class="table table-hover"><thead><tr>'
				.'<th>'.$this->lang('Titre').'</th>'
				.'<th>'.$this->lang('Adresse').'</th>'
				.'<th>'.$this->lang('Coordonnées').'</th>'
				.'<th>'.$this->lang('Catégorie').'</th>'
				.'<th class="text-end"></th></tr></thead><tbody>';

			foreach ($lieux as $l)
			{
				$slug   = url_title($l['title']);
				$publie = !empty($l['published']);
				$lat    = Place::latitude($l['latitude']);
				$lon    = Place::longitude($l['longitude']);

				$corps .= '<tr>'
					.'<td><strong>'.htmlspecialchars((string) ($l['title'])).'</strong>'
					.(!$publie ? ' <span class="badge text-bg-light">'.$this->lang('Brouillon').'</span>' : '')
					.'</td>'
					.'<td class="text-muted">'.htmlspecialchars((string) ($l['address'])).'</td>'
					.'<td class="text-muted">'
					.($lat !== NULL && $lon !== NULL
						? '<a href="'.htmlspecialchars((string) (Place::lien_osm($lat, $lon)), ENT_QUOTES).'" target="_blank" rel="noopener">'
							.htmlspecialchars((string) (Place::nombre($lat).', '.Place::nombre($lon))).'</a>'
						// Une ligne dont les coordonnées ne sont pas exploitables est invisible sur la
						// page publique : l'administration doit le dire, sinon le lieu disparaît sans mot.
						: '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> '.$this->lang('Coordonnées illisibles').'</span>')
					.'</td>'
					.'<td>'.htmlspecialchars((string) ($l['cat_title'])).'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/places/p/'.$l['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/places/p/delete/'.$l['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ce lieu ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$corps .= '</tbody></table>';
		}

		// ── Barre de recherche et de filtre (GET, préservé par la pagination) ──
		$action = url($this->module->pagination->get_url());
		$barre  = '<form method="get" action="'.$action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$barre .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filtres['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher un lieu, une adresse, une description…')), ENT_QUOTES).'" style="max-width:260px;">';
		$barre .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$barre .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';

		foreach ($cats as $c)
		{
			$barre .= '<option value="'.(int) $c['id'].'"'.((int) $filtres['category'] === (int) $c['id'] ? ' selected' : '').'>'.htmlspecialchars((string) ($c['title'])).'</option>';
		}

		$barre .= '</select>';
		$barre .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';

		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiés'), 'draft' => $this->lang('Brouillons')] as $valeur => $libelle)
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

		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/places/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';
		$actions      = '<a class="btn btn-primary btn-sm" href="'.url('admin/places/p/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau lieu').'</a>';
		$sous_titre   = $publies.' '.$this->lang('publié|publiés', $publies).($brouillons > 0 ? ' · '.$brouillons.' '.$this->lang('brouillon|brouillons', $brouillons) : '');

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_corps, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-map-marked-alt', $this->lang('Lieux'), $corps, $sous_titre, $actions).'</div>'
			.'</div>';
	}

	// ── Lieux ─────────────────────────────────────────────────────────────────

	public function _p_add()    { return $this->_p_form(NULL); }
	public function _p_edit($p) { return $this->_p_form($p); }

	public function _p_delete($p)
	{
		$this->check_csrf('admin/places');

		NeoFrag()->db->where('id', $p['id'])->delete('nf_places');
		notify($this->lang('Lieu supprimé.'));
		redirect('admin/places');
	}

	protected function _p_form($p)
	{
		$nouveau = $p === NULL;
		$this->title($nouveau ? $this->lang('Nouveau lieu') : $this->lang('Éditer le lieu'))->icon('fas fa-map-marker-alt')->breadcrumb();

		$cats  = NeoFrag()->db->select('id', 'title')->from('nf_places_categories')->order_by('sort_order ASC')->get();
		$liste = [];
		foreach ($cats as $c) { $liste[$c['id']] = $c['title']; }

		if (!$liste)
		{
			// Sans catégorie, `category_id` n'aurait aucune valeur possible : on le dit plutôt que de
			// présenter un formulaire qui ne peut pas être validé.
			notify($this->lang('Créez d\'abord une catégorie.'));
			redirect('admin/places/cat/add');
		}

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $liste, 'value' => $nouveau ? key($liste) : $p['category_id'], 'rules' => 'required'],
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouveau ? '' : $p['title'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => $nouveau ? '' : $p['description']],
				'address'     => ['label' => $this->lang('Adresse'), 'type' => 'text', 'value' => $nouveau ? '' : $p['address']],
				'latitude'    => ['label' => $this->lang('Latitude'), 'type' => 'text', 'value' => $nouveau ? '' : Place::nombre((float) $p['latitude']), 'rules' => 'required'],
				'longitude'   => ['label' => $this->lang('Longitude'), 'type' => 'text', 'value' => $nouveau ? '' : Place::nombre((float) $p['longitude']), 'rules' => 'required'],
				'link'        => ['label' => $this->lang('Site du lieu'), 'type' => 'text', 'value' => $nouveau ? '' : $p['link']],
				'sort_order'  => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouveau ? '0' : $p['sort_order']],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Lieu publié')], 'checked' => ['1' => ($nouveau || !empty($p['published']))]]
			 ])
			 ->add_submit($nouveau ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouveau ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$lat = Place::latitude($post['latitude']);
			$lon = Place::longitude($post['longitude']);

			if ($lat === NULL || $lon === NULL)
			{
				// On refuse PLUTÔT que d'enregistrer un lieu qui disparaîtrait de la carte sans un mot.
				notify($this->lang('Coordonnées hors bornes : la latitude va de -90 à 90, la longitude de -180 à 180.'));
			}
			else
			{
				$donnees = [
					'category_id' => (int) $post['category_id'],
					'title'       => $post['title'],
					'description' => $post['description'],
					'address'     => $post['address'],
					'latitude'    => Place::nombre($lat),
					'longitude'   => Place::nombre($lon),
					'link'        => Place::lien_sur($post['link']),
					'sort_order'  => (int) $post['sort_order'],
					'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
				];

				if ($nouveau) NeoFrag()->db->insert('nf_places', $donnees);
				else          NeoFrag()->db->where('id', $p['id'])->update('nf_places', $donnees);

				notify($nouveau ? $this->lang('Lieu créé.') : $this->lang('Lieu modifié.'));
				redirect('admin/places');
			}
		}

		$aide = '<div class="alert alert-info">'
			.'<i class="fas fa-info-circle"></i> '
			.$this->lang('Pour trouver les coordonnées d\'un lieu, ouvrez %s, faites un clic droit sur l\'endroit puis « Afficher l\'adresse ». La latitude vient en premier.',
				'<a href="https://www.openstreetmap.org/" target="_blank" rel="noopener">openstreetmap.org</a>')
			.'</div>';

		return $this->admin_card($nouveau ? 'fas fa-plus' : 'fas fa-edit', $nouveau ? $this->lang('Nouveau lieu') : $this->lang('Éditer le lieu'), $aide.$this->form()->display());
	}

	// ── Catégories ────────────────────────────────────────────────────────────

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }

	public function _cat_delete($c)
	{
		$this->check_csrf('admin/places');

		$nb = (int) NeoFrag()->db->select('COUNT(*)')->from('nf_places')->where('category_id', $c['id'])->row();

		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d lieu(x) dans cette catégorie.', $nb));
			redirect('admin/places');
		}

		NeoFrag()->db->where('id', $c['id'])->delete('nf_places_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/places');
	}

	protected function _cat_form($c)
	{
		$nouvelle = $c === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'      => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouvelle ? '' : $c['title'], 'rules' => 'required'],
				'icon'       => ['label' => $this->lang('Icône'), 'type' => 'iconpicker', 'value' => $nouvelle ? 'fas fa-map-marker-alt' : $c['icon']],
				'color'      => ['label' => $this->lang('Couleur du marqueur'), 'type' => 'colorpicker', 'value' => $nouvelle ? '' : $c['color']],
				'sort_order' => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouvelle ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = [
				'title'      => $post['title'],
				'icon'       => $post['icon'] !== '' ? $post['icon'] : 'fas fa-map-marker-alt',
				// La couleur part dans un `style` côté navigateur : seule la notation hexadécimale
				// passe, le reste vaut « la couleur du thème ».
				'color'      => Place::couleur($post['color']),
				'sort_order' => (int) $post['sort_order']
			];

			if ($nouvelle) NeoFrag()->db->insert('nf_places_categories', $donnees);
			else           NeoFrag()->db->where('id', $c['id'])->update('nf_places_categories', $donnees);

			notify($nouvelle ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/places');
		}

		return $this->admin_card($nouvelle ? 'fas fa-folder-plus' : 'fas fa-folder-open', $nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie : %s', $c['title']), $this->form()->display());
	}
}
