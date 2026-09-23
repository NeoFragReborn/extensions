<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($articles, $filters)
	{
		$this	->title($this->lang('Articles'))
				->icon('far fa-newspaper');

		// Actions en masse (POST) : publier / dépublier la sélection, puis refresh.
		if (!empty($_POST['bulk_action']) && !empty($_POST['selected']) && is_array($_POST['selected']))
		{
			$ids    = array_values(array_filter(array_map('intval', $_POST['selected'])));
			$action = (string)$_POST['bulk_action'];

			if ($ids && in_array($action, ['publish', 'unpublish'], TRUE))
			{
				NeoFrag()->db->where('article_id', $ids)->update('nf_articles', ['published' => $action === 'publish' ? 1 : 0]);
				notify($this->lang('%d article mis à jour.|%d articles mis à jour.', count($ids), count($ids)));
				redirect('admin/articles');
			}
		}

		$published_count = (int)$filters['published'];
		$drafts_count    = (int)$filters['drafts'];

		if (empty($articles))
		{
			$body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucun article ne correspond à ces critères.'))
				: $this->admin_empty('far fa-newspaper', $this->lang('Aucun article pour le moment.'));
		}
		else
		{
			$body = '<div class="nf-card-grid">';
			foreach ($articles as $a)
			{
				$slug      = url_title($a['title']);
				$published = !empty($a['published']);
				$excerpt   = !empty($a['excerpt']) ? trim(strip_tags($a['excerpt'])) : '';

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title"><input type="checkbox" name="selected[]" value="'.(int)$a['article_id'].'" class="nf-bulk-cb" style="margin-right:6px;vertical-align:middle;"><a href="'.url('articles/'.$a['article_id'].'/'.$slug).'">'.htmlspecialchars((string) ($a['title'])).'</a></div>';
				$is_scheduled = $published && !empty($a['date']) && strtotime($a['date']) > time();
				if (!$published)       { $st_cls = 'draft';     $st_icon = 'fa-clock';        $st_lbl = $this->lang('Brouillon'); }
				elseif ($is_scheduled) { $st_cls = 'scheduled'; $st_icon = 'fa-calendar-alt'; $st_lbl = $this->lang('Programmé le %s', timetostr($this->lang('d/m/Y H:i'), $a['date'])); }
				else                   { $st_cls = 'published'; $st_icon = 'fa-check';        $st_lbl = $this->lang('Publié'); }
				$body .= '<span class="nf-content-card-status '.$st_cls.'">';
				$body .= '<i class="fas '.$st_icon.'"></i> '.$st_lbl;
				$body .= '</span>';
				$body .= '</div>';

				if ($excerpt)
				{
					$body .= '<div class="nf-content-card-desc">'.htmlspecialchars((string) ($excerpt)).'</div>';
				}

				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="fas fa-folder"></i> '.htmlspecialchars((string) ($a['category_title'] ?? '—')).'</span>';
				$body .= '<span><i class="fas fa-user"></i> '.htmlspecialchars((string) ($a['username'] ?? '—')).'</span>';
				$body .= '<span><i class="far fa-clock"></i> '.timetostr('j M Y', $a['date']).'</span>';
				$body .= '<span title="'.$this->lang('Vues').'"><i class="far fa-eye"></i> '.number_format((int)$a['views'], 0, ',', ' ').'</span>';
				$body .= '</div>';

				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/articles/'.$a['article_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/articles/delete/'.$a['article_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer cet article ? Action irréversible.')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars((string) ($filters['q'])).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars((string) ($this->lang('Rechercher un titre…')), ENT_QUOTES).'" style="max-width:240px;">';
		$toolbar .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$toolbar .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';
		foreach ($filters['categories'] as $cid => $ctitle)
		{
			$toolbar .= '<option value="'.(int)$cid.'"'.((int)$filters['category'] === (int)$cid ? ' selected' : '').'>'.htmlspecialchars((string) ($ctitle)).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiés'), 'draft' => $this->lang('Brouillons')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.htmlspecialchars((string) ($label)).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= $this->sort_select($filters['sort_cols'], $filters['sort']);
		$toolbar .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';
		if (!empty($filters['active']))
		{
			$toolbar .= '<a href="'.$form_action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
			$toolbar .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int)$filters['matched'], (int)$filters['matched']).'</span>';
		}
		$toolbar .= '</form>';

		$pagination = (string)$this->module->pagination->get_pagination();
		if ($pagination !== '')
		{
			$pagination = '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
		}

		// Enveloppe la grille dans un formulaire POST + barre d'action groupée (publier/dépublier).
		if (!empty($articles))
		{
			$bulk_bar = '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">'
				.'<label style="display:flex;align-items:center;gap:6px;font-size:13px;margin:0;cursor:pointer;"><input type="checkbox" id="nf-bulk-all-articles"> '.$this->lang('Tout sélectionner').'</label>'
				.'<select name="bulk_action" class="form-select form-select-sm" style="width:auto;" required>'
					.'<option value="">'.$this->lang('Action groupée…').'</option>'
					.'<option value="publish">'.$this->lang('Publier').'</option>'
					.'<option value="unpublish">'.$this->lang('Dépublier').'</option>'
				.'</select>'
				.'<button type="submit" class="btn btn-sm btn-primary">'.$this->lang('Appliquer').'</button>'
				.'</div>';

			$body = '<form method="post" action="'.htmlspecialchars((string) (url($this->url->request)), ENT_QUOTES).'">'.$bulk_bar.$body.'</form>'
				.'<script>(function(){var a=document.getElementById("nf-bulk-all-articles");if(a){a.addEventListener("change",function(){document.querySelectorAll(".nf-bulk-cb").forEach(function(c){c.checked=a.checked;});});}})();</script>';
		}

		$body = $toolbar.$body.$pagination;

		$subtitle = $published_count.' '.$this->lang('publié|publiés', $published_count).($drafts_count > 0 ? ' · '.$drafts_count.' '.$this->lang('brouillon|brouillons', $drafts_count) : '');
		$actions  = '<a class="btn btn-secondary btn-sm" href="'.url('admin/articles/categories/add').'"><i class="fas fa-folder-plus"></i> '.$this->lang('Catégorie').'</a> '
			.'<a class="btn btn-primary btn-sm" href="'.url('admin/articles/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvel article').'</a>';

		return $this->admin_card('far fa-newspaper', $this->lang('Articles'), $body, $subtitle, $actions);
	}

	public function _add()
	{
		return $this->_edit_form(NULL);
	}

	public function _delete($article)
	{
		$this->check_csrf('admin/articles');

		// Soft-delete : l'article part à la corbeille (restaurable).
		NeoFrag()->db	->where('article_id', $article['article_id'])
						->update('nf_articles', [
							'deleted_at' => date('Y-m-d H:i:s'),
							'deleted_by' => $this->user() ? (int)$this->user->id : NULL
						]);

		notify($this->lang('Article envoyé à la corbeille.'));
		redirect('admin/articles');
	}

	public function _edit($article)
	{
		return $this->_edit_form($article);
	}

	protected function _edit_form($article)
	{
		$is_new = $article === NULL;

		if ($is_new)
		{
			$this->title($this->lang('Nouvel article'))->icon('fas fa-plus')->breadcrumb();
		}
		else
		{
			$this->title($this->lang('Éditer : %s', $article['title']))->icon('fas fa-edit')->breadcrumb();
		}

		// Categories pour le select
		$categories_rows = NeoFrag()->db	->select('c.category_id', 'cl.title')
											->from('nf_articles_categories c')
											->join('nf_articles_categories_lang cl', 'c.category_id = cl.category_id')
											->where('cl.lang', $this->config->lang->info()->name)
											->order_by('cl.title')
											->get();
		$categories_array = [];
		foreach ($categories_rows as $row)
		{
			$categories_array[$row['category_id']] = $row['title'];
		}

		$this->form()
			 ->add_rules([
				'title' => [
					'label' => $this->lang('Titre'),
					'type'  => 'text',
					'value' => $is_new ? '' : $article['title'],
					'rules' => 'required'
				],
				'category_id' => [
					'label' => $this->lang('Catégorie'),
					'type'  => 'select',
					'values' => $categories_array,
					'value' => $is_new ? key($categories_array) : $article['category_id'],
					'rules' => 'required'
				],
				'image' => [
					'label'  => $this->lang('Image à la une'),
					'type'   => 'file',
					'upload' => 'articles',
					'value'  => $is_new ? '' : $article['image_id'],
					'info'   => $this->lang('Image (max. %d Mo). Sinon, image de la catégorie.', file_upload_max_size() / 1024 / 1024),
					'check'  => function($filename, $ext){
						if (!in_array($ext, ['gif', 'jpeg', 'jpg', 'png', 'webp']))
						{
							return $this->lang('Veuillez choisir un fichier image');
						}
					}
				],
				'excerpt' => [
					'label'       => $this->lang('Extrait (optionnel)'),
					'type'        => 'textarea',
					'value'       => $is_new ? '' : $article['excerpt'],
					'description' => $this->lang('Affiché sur la liste des articles. Si vide, généré depuis le début du contenu.')
				],
				'content' => [
					'label' => $this->lang('Contenu'),
					'type'  => 'editor',
					'value' => $is_new ? '' : $article['content'],
					'rules' => 'required',
					'description' => $this->lang('Éditeur riche TinyMCE. Utilise les niveaux de titre (Heading 2/3) pour structurer (sommaire généré auto).')
				],
				'tags' => [
					'label' => $this->lang('Tags'),
					'type'  => 'text',
					'value' => $is_new ? '' : $article['tags'],
					'description' => $this->lang('Séparés par des virgules.')
				],
				'date' => [
					'label'       => $this->lang('Date de publication'),
					'type'        => 'datetime',
					'value'       => $is_new ? '' : $article['date'],
					'description' => $this->lang('Une date future programme la publication : l\'article reste masqué publiquement jusqu\'à cette date (si publié).')
				],
				'published' => [
					'label'  => $this->lang('Publier'),
					'type'   => 'checkbox',
					'value' => ['1'], 'values' => ['1' => $this->lang('Article publié (visible publiquement)')], 'checked' => ['1' => (!$is_new && !empty($article['published']))]
				]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$lang = $this->config->lang->info()->name;
			$published = in_array('1', $post['published'] ?? []) ? '1' : '0';

			if ($is_new)
			{
				NeoFrag()->db->insert('nf_articles', [
					'category_id' => (int)$post['category_id'],
					'user_id'     => $this->user->id,
					'image_id'    => $post['image'] ?: NULL,
					'date'        => !empty($post['date']) ? $post['date'] : NeoFrag()->date()->sql(),
					'published'   => $published,
					'views'       => 0
				]);
				$new_id = (int)NeoFrag()->db->driver()->insert_id();

				NeoFrag()->db->insert('nf_articles_lang', [
					'article_id' => $new_id,
					'lang'       => $lang,
					'title'      => $post['title'],
					'excerpt'    => $post['excerpt'] ?? '',
					'content'    => $post['content'],
					'tags'       => $post['tags'] ?? ''
				]);

				$this->_snapshot($new_id, $post, $this->lang('Création'));

				// Parution : émet event/webhook/gamification/notifications. Ne fait rien si programmé
				// (date future) → l'endpoint de parution (cron) s'en chargera à l'heure réelle.
				$this->model()->announce($new_id);

				notify($this->lang('Article créé.'));
				redirect('admin/articles');
			}
			else
			{
				NeoFrag()->db	->where('article_id', $article['article_id'])
								->update('nf_articles', array_merge([
									'category_id' => (int)$post['category_id'],
									'image_id'   => $post['image'] ?: NULL,
									'published'   => $published
								], !empty($post['date']) ? ['date' => $post['date']] : []));

				NeoFrag()->db	->where('article_id', $article['article_id'])
								->where('lang', $lang)
								->update('nf_articles_lang', [
									'title'   => $post['title'],
									'excerpt' => $post['excerpt'] ?? '',
									'content' => $post['content'],
									'tags'    => $post['tags'] ?? ''
								]);

				$this->_snapshot($article['article_id'], $post, $this->lang('Édition'));

				// Parution si l'édition rend l'article publiable maintenant (brouillon → publié,
				// ou date avancée à maintenant). No-op si déjà annoncé ou encore programmé.
				$this->model()->announce($article['article_id']);

				notify($this->lang('Article modifié.'));
				redirect('admin/articles');
			}
		}

		$history_btn = $is_new ? '' : '<a class="btn btn-sm btn-light" href="'.url('admin/articles/history/'.(int)$article['article_id'].'/'.url_title($article['title'])).'">'.icon('fas fa-history').' '.$this->lang('Historique').'</a>';

		return $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouvel article') : $this->lang('Éditer : %s', $article['title']), $this->form()->display(), '', $history_btn);
	}

	private function _snapshot($article_id, array $post, $summary)
	{
		if ($article_id && ($revisions = $this->module('revisions')))
		{
			$revisions->snapshot('article', (int)$article_id, [
				'title'   => (string)($post['title']   ?? ''),
				'excerpt' => (string)($post['excerpt'] ?? ''),
				'content' => (string)($post['content'] ?? ''),
				'tags'    => (string)($post['tags']    ?? '')
			], $this->config->lang->info()->name, $summary);
		}
	}

	public function _history($article)
	{
		$this->title($this->lang('Historique des révisions'))->icon('fas fa-history')->breadcrumb();

		$panel = ($revisions = $this->module('revisions'))
			? $revisions->history_panel('article', (int)$article['article_id'], 'admin/articles/revision/restore/'.(int)$article['article_id'].'/'.url_title($article['title']))
			: '';

		return $this->admin_card('fas fa-history', $this->lang('Historique').' — '.$article['title'], $panel);
	}

	public function _revision_restore($article, $revision_id)
	{
		$revisions = $this->module('revisions');
		$revision  = $revisions ? $revisions->get($revision_id) : NULL;
		$history   = 'admin/articles/history/'.(int)$article['article_id'].'/'.url_title($article['title']);

		$this->title($this->lang('Restaurer une révision'))->icon('fas fa-undo')->breadcrumb();

		if (!$revision || $revision['content_type'] !== 'article' || (int)$revision['content_id'] !== (int)$article['article_id'])
		{
			notify($this->lang('Révision introuvable.'), 'danger');
			redirect('admin/articles');
		}

		// form2 (champ caché => POST porte le token, déclenche success). CSRF géré par form2.
		$form = $this->form2()
			->rule($this->form_hidden('confirm', '1'))
			->success(function($data) use ($article, $revision, $revision_id, $history){
				$fields = $revision['fields'];
				$lang   = $revision['lang'] ?: $this->config->lang->info()->name;

				NeoFrag()->db	->where('article_id', $article['article_id'])
								->where('lang', $lang)
								->update('nf_articles_lang', [
									'title'   => $fields['title']   ?? '',
									'excerpt' => $fields['excerpt'] ?? '',
									'content' => $fields['content'] ?? '',
									'tags'    => $fields['tags']    ?? ''
								]);

				$this->_snapshot($article['article_id'], $fields, $this->lang('Restauration depuis #%d', (int)$revision_id));

				notify($this->lang('Version restaurée.'));

				redirect($history);
			})
			->submit($this->lang('Restaurer'));

		return $this->admin_back($history, $this->lang('Historique'))
			.$this->admin_card('fas fa-undo', $this->lang('Restaurer la révision #%d', (int)$revision_id),
				'<p>'.$this->lang('Le contenu actuel sera remplacé par cette version. La version actuelle reste conservée dans l\'historique.').'</p>'.$form);
	}

	public function _categories_add()
	{
		$this->title($this->lang('Nouvelle catégorie'))->icon('fas fa-plus')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title' => ['label' => $this->lang('Titre'), 'type' => 'text', 'rules' => 'required'],
				'name'  => ['label' => $this->lang('Slug (URL)'), 'type' => 'text', 'rules' => 'required',
				            'description' => $this->lang('Identifiant URL en minuscules, sans accent ni espace.')]
			 ])
			 ->add_submit($this->lang('Créer'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			NeoFrag()->db->insert('nf_articles_categories', [
				'name' => $post['name']
			]);
			$cat_id = (int)NeoFrag()->db->driver()->insert_id();

			foreach (['fr', 'en'] as $lang)
			{
				NeoFrag()->db->insert('nf_articles_categories_lang', [
					'category_id' => $cat_id,
					'lang'        => $lang,
					'title'       => $post['title']
				]);
			}

			notify($this->lang('Catégorie créée.'));
			redirect('admin/articles');
		}

		return $this->admin_card('fas fa-folder-plus', $this->lang('Nouvelle catégorie'), $this->form()->display());
	}

	public function _categories_edit($category)
	{
		$this->title($this->lang('Éditer la catégorie : %s', $category['title']))->icon('fas fa-edit')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title' => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $category['title'], 'rules' => 'required'],
				'name'  => ['label' => $this->lang('Slug (URL)'), 'type' => 'text', 'value' => $category['name'], 'rules' => 'required']
			 ])
			 ->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			NeoFrag()->db	->where('category_id', $category['category_id'])
							->update('nf_articles_categories', ['name' => $post['name']]);

			NeoFrag()->db	->where('category_id', $category['category_id'])
							->where('lang', $this->config->lang->info()->name)
							->update('nf_articles_categories_lang', ['title' => $post['title']]);

			notify($this->lang('Catégorie modifiée.'));
			redirect('admin/articles');
		}

		return $this->admin_card('fas fa-folder-open', $this->lang('Éditer la catégorie : %s', $category['title']), $this->form()->display());
	}

	public function _categories_delete($category)
	{
		// Vérifier qu'il n'y a pas d'articles dans cette catégorie
		$count = (int)NeoFrag()->db	->select('COUNT(*)')
									->from('nf_articles')
									->where('category_id', $category['category_id'])
									->row(FALSE);

		if ($count > 0)
		{
			notify($this->lang('Impossible : %d article(s) dans cette catégorie. Déplace-les ou supprime-les d\'abord.', $count));
			redirect('admin/articles');
		}

		NeoFrag()->db	->from('nf_articles_categories')
						->where('category_id', $category['category_id'])
						->delete();
		NeoFrag()->db	->from('nf_articles_categories_lang')
						->where('category_id', $category['category_id'])
						->delete();

		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/articles');
	}
}
