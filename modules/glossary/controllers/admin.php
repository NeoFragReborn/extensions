<?php
declare(strict_types=1);
namespace NF\Modules\Glossary\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Glossary\Lib\Term;

class Admin extends Controller_Module
{
	/** Ce qu'on montre d'une définition dans sa fiche, avant de la couper. */
	const APERCU = 180;

	public function index($cats, $termes, $filtres)
	{
		$this->title($this->lang('Dictionnaire'))->icon('fas fa-book');

		// ── Colonne des catégories ────────────────────────────────────────────
		if (empty($cats))
		{
			$cats_corps = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		}
		else
		{
			$cats_corps = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Termes').'</th><th class="text-end"></th></tr></thead><tbody>';

			foreach ($cats as $c)
			{
				$slug = url_title($c['title']);
				$cats_corps .= '<tr>'
					.'<td><strong>'.htmlspecialchars((string) ($c['title'])).'</strong></td>'
					.'<td class="text-end">'.(int) $c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/glossary/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/glossary/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cette catégorie ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$cats_corps .= '</tbody></table>';
		}

		// ── Colonne des termes ────────────────────────────────────────────────
		$publies    = (int) $filtres['published'];
		$brouillons = (int) $filtres['drafts'];

		if (empty($termes))
		{
			$corps = !empty($filtres['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun terme ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-book', $this->lang('Aucun terme.'));
		}
		else
		{
			$corps = '<table class="table table-hover"><thead><tr>'
				.'<th class="text-nowrap" style="width:1%;">'.$this->lang('Lettre').'</th>'
				.'<th>'.$this->lang('Terme').'</th>'
				.'<th>'.$this->lang('Définition').'</th>'
				.'<th>'.$this->lang('Catégorie').'</th>'
				.'<th class="text-end"></th></tr></thead><tbody>';

			foreach ($termes as $t)
			{
				$slug    = url_title($t['term']);
				$publie  = !empty($t['published']);
				$definition = trim((string) preg_replace('/\s+/u', ' ', (string) $t['definition']));
				$apercu  = mb_strlen($definition) > self::APERCU ? mb_substr($definition, 0, self::APERCU).'…' : $definition;

				$corps .= '<tr>'
					.'<td><span class="badge text-bg-secondary">'.htmlspecialchars((string) ($t['initial'])).'</span></td>'
					.'<td><strong>'.htmlspecialchars((string) ($t['term'])).'</strong>'
					.(!$publie ? ' <span class="badge text-bg-light">'.$this->lang('Brouillon').'</span>' : '')
					.'</td>'
					.'<td class="text-muted">'.htmlspecialchars((string) ($apercu)).'</td>'
					.'<td>'.htmlspecialchars((string) ($t['cat_title'])).'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/glossary/t/'.$t['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/glossary/t/delete/'.$t['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ce terme ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}

			$corps .= '</tbody></table>';
		}

		// ── Barre de recherche et de filtre (GET, préservé par la pagination) ──
		$action = url($this->module->pagination->get_url());
		$barre  = '<form method="get" action="'.$action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$barre .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filtres['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher un terme, une définition, un synonyme…')), ENT_QUOTES).'" style="max-width:260px;">';
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

		$cats_actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/glossary/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';
		$actions      = '<a class="btn btn-primary btn-sm" href="'.url('admin/glossary/t/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau terme').'</a>';
		$sous_titre   = $publies.' '.$this->lang('publié|publiés', $publies).($brouillons > 0 ? ' · '.$brouillons.' '.$this->lang('brouillon|brouillons', $brouillons) : '');

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_corps, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-book', $this->lang('Termes'), $corps, $sous_titre, $actions).'</div>'
			.'</div>';
	}

	// ── Termes ────────────────────────────────────────────────────────────────

	public function _t_add()    { return $this->_t_form(NULL); }
	public function _t_edit($t) { return $this->_t_form($t); }

	public function _t_delete($t)
	{
		$this->check_csrf('admin/glossary');

		NeoFrag()->db->where('id', $t['id'])->delete('nf_glossary_terms');
		notify($this->lang('Terme supprimé.'));
		redirect('admin/glossary');
	}

	protected function _t_form($t)
	{
		$nouveau = $t === NULL;
		$this->title($nouveau ? $this->lang('Nouveau terme') : $this->lang('Éditer le terme'))->icon('fas fa-book')->breadcrumb();

		$cats  = NeoFrag()->db->select('id', 'title')->from('nf_glossary_categories')->order_by('sort_order ASC')->get();
		$liste = [];
		foreach ($cats as $c) { $liste[$c['id']] = $c['title']; }

		if (!$liste)
		{
			// Sans catégorie, `category_id` n'aurait aucune valeur possible : on le dit plutôt que de
			// présenter un formulaire qui ne peut pas être validé.
			notify($this->lang('Créez d\'abord une catégorie.'));
			redirect('admin/glossary/cat/add');
		}

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $liste, 'value' => $nouveau ? key($liste) : $t['category_id'], 'rules' => 'required'],
				'term'        => ['label' => $this->lang('Terme'), 'type' => 'text', 'value' => $nouveau ? '' : $t['term'], 'rules' => 'required'],
				'definition'  => ['label' => $this->lang('Définition'), 'type' => 'textarea', 'value' => $nouveau ? '' : $t['definition'], 'rules' => 'required'],
				'synonyms'    => ['label' => $this->lang('Synonymes (séparés par des virgules)'), 'type' => 'text', 'value' => $nouveau ? '' : $t['synonyms']],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Terme publié')], 'checked' => ['1' => ($nouveau || !empty($t['published']))]]
			 ])
			 ->add_submit($nouveau ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouveau ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = [
				'category_id' => (int) $post['category_id'],
				'term'        => $post['term'],
				// La lettre est CALCULÉE, jamais saisie : un terme et sa lettre ne peuvent pas diverger,
				// et l'administrateur n'a pas à se demander sous quelle lettre ranger « Éclaireur ».
				'initial'     => Term::initiale($post['term']),
				'definition'  => $post['definition'],
				'synonyms'    => implode(', ', Term::synonymes($post['synonyms'])),
				'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($nouveau) NeoFrag()->db->insert('nf_glossary_terms', $donnees);
			else          NeoFrag()->db->where('id', $t['id'])->update('nf_glossary_terms', $donnees);

			notify($nouveau ? $this->lang('Terme créé.') : $this->lang('Terme modifié.'));
			redirect('admin/glossary');
		}

		return $this->admin_card($nouveau ? 'fas fa-plus' : 'fas fa-edit', $nouveau ? $this->lang('Nouveau terme') : $this->lang('Éditer le terme'), $this->form()->display());
	}

	// ── Catégories ────────────────────────────────────────────────────────────

	public function _cat_add()    { return $this->_cat_form(NULL); }
	public function _cat_edit($c) { return $this->_cat_form($c); }

	public function _cat_delete($c)
	{
		$this->check_csrf('admin/glossary');

		$nb = (int) NeoFrag()->db->select('COUNT(*)')->from('nf_glossary_terms')->where('category_id', $c['id'])->row();

		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d terme(s) dans cette catégorie.', $nb));
			redirect('admin/glossary');
		}

		NeoFrag()->db->where('id', $c['id'])->delete('nf_glossary_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/glossary');
	}

	protected function _cat_form($c)
	{
		$nouvelle = $c === NULL;
		$this->title($nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'      => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $nouvelle ? '' : $c['title'], 'rules' => 'required'],
				'sort_order' => ['label' => $this->lang('Ordre de tri'), 'type' => 'text', 'value' => $nouvelle ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($nouvelle ? $this->lang('Créer') : $this->lang('Enregistrer'), $nouvelle ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$donnees = ['title' => $post['title'], 'sort_order' => (int) $post['sort_order']];

			if ($nouvelle) NeoFrag()->db->insert('nf_glossary_categories', $donnees);
			else           NeoFrag()->db->where('id', $c['id'])->update('nf_glossary_categories', $donnees);

			notify($nouvelle ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/glossary');
		}

		return $this->admin_card($nouvelle ? 'fas fa-folder-plus' : 'fas fa-folder-open', $nouvelle ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer la catégorie : %s', $c['title']), $this->form()->display());
	}
}
