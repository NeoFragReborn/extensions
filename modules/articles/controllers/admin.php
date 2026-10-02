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
		if ($this->is_authorized('modify_articles') && !empty($_POST['bulk_action']) && !empty($_POST['selected']) && is_array($_POST['selected']))
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
				$body .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/articles/'.$a['article_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
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
		$actions  = '<a class="btn btn-secondary btn-sm" href="'.url('admin/articles/categories').'"><i class="fas fa-folder"></i> '.$this->lang('Catégories').'</a> '
			.'<a class="btn btn-secondary btn-sm" href="'.url('admin/articles/series').'"><i class="fas fa-layer-group"></i> '.$this->lang('Séries').'</a> '
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
											->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id')
											->order_by('cl.title')
											->get();
		$categories_array = [];
		foreach ($categories_rows as $row)
		{
			$categories_array[$row['category_id']] = $row['title'];
		}

		// Les séries : un billet peut être une partie de l'une d'elles, à son rang.
		$series_array = ['0' => $this->lang('Aucune')];
		foreach ($this->_modele()->get_series_list() as $s)
		{
			$series_array[$s['series_id']] = $s['title'];
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
				'series_id' => [
					'label'       => $this->lang('Série'),
					'type'        => 'select',
					'values'      => $series_array,
					'value'       => $is_new ? '0' : (string) (int) ($article['series_id'] ?? 0),
					'description' => $this->lang('Un billet en plusieurs parties : chacune affiche la liste des autres.')
				],
				'series_order' => [
					'label'       => $this->lang('Partie n°'),
					'type'        => 'number',
					'value'       => $is_new ? '' : (string) (int) ($article['series_order'] ?? 0),
					'description' => $this->lang('Le rang du billet dans sa série.')
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
				],
				'featured' => [
					'label'   => $this->lang('À la une'),
					'type'    => 'checkbox',
					'value'   => ['1'], 'values' => ['1' => $this->lang('Ouvrir la liste du Blog avec ce billet')], 'checked' => ['1' => (!$is_new && !empty($article['featured']))]
				]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$lang = $this->config->lang->info()->name;
			$published = in_array('1', $post['published'] ?? []) ? '1' : '0';
			$featured  = in_array('1', $post['featured'] ?? []) ? 1 : 0;
			$serie     = isset($series_array[(int) ($post['series_id'] ?? 0)]) && (int) ($post['series_id'] ?? 0) ? (int) $post['series_id'] : NULL;
			$rang      = $serie ? max(0, min(65535, (int) ($post['series_order'] ?? 0))) : 0;

			if ($is_new)
			{
				NeoFrag()->db->insert('nf_articles', [
					'category_id' => (int)$post['category_id'],
					'user_id'     => $this->user->id,
					'image_id'    => $post['image'] ?: NULL,
					'date'        => !empty($post['date']) ? $post['date'] : NeoFrag()->date()->sql(),
					'published'   => $published,
					'featured'    => $featured,
					'series_id'   => $serie,
					'series_order' => $rang,
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
									'published'   => $published,
									'featured'    => $featured,
									'series_id'   => $serie,
									'series_order' => $rang
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
			redirect('admin/articles/categories');
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
			redirect('admin/articles/categories');
		}

		return $this->admin_card('fas fa-folder-open', $this->lang('Éditer la catégorie : %s', $category['title']), $this->form()->display());
	}

	/** La liste des catégories : chacune, son nombre de billets, et de quoi la modifier. */
	public function _categories($categories)
	{
		$this->title($this->lang('Catégories'))->icon('fas fa-folder')->breadcrumb();

		$lignes = '';

		foreach ($categories as $c)
		{
			$slug    = url_title((string) $c['title']);
			$lignes .= '<tr><td>'.htmlspecialchars((string) $c['title']).' <small class="text-muted">/'.htmlspecialchars((string) $c['name']).'</small></td><td>'.(int) $c['articles_count'].'</td><td class="text-end">'
				.($this->is_authorized('modify_categories') ? '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/articles/categories/'.$c['category_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'">'.icon('fas fa-pen').'</a> ' : '')
				.($this->is_authorized('delete_categories') && !(int) $c['articles_count'] ? '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/articles/categories/delete/'.$c['category_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) $this->lang('Supprimer cette catégorie ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'">'.icon('far fa-trash-alt').'</a>' : '')
				.'</td></tr>';
		}

		$corps = $lignes
			? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>'.$this->lang('Catégorie').'</th><th>'.$this->lang('Billets').'</th><th></th></tr></thead><tbody>'.$lignes.'</tbody></table></div>'
			: $this->admin_empty('fas fa-folder', $this->lang('Aucune catégorie pour le moment.'));

		$actions = $this->is_authorized('add_categories') ? '<a class="btn btn-primary btn-sm" href="'.url('admin/articles/categories/add').'"><i class="fas fa-folder-plus"></i> '.$this->lang('Nouvelle catégorie').'</a>' : '';

		return $this->admin_back('admin/articles').$this->admin_card('fas fa-folder', $this->lang('Catégories'), $corps, '', $actions);
	}

	/** Les séries : un billet en plusieurs parties, avec leur navigation. */
	public function _series($series)
	{
		$this->title($this->lang('Séries'))->icon('fas fa-layer-group')->breadcrumb();

		$lignes = '';

		foreach ($series as $s)
		{
			$slug    = url_title($s['title']);
			$lignes .= '<tr><td>'.htmlspecialchars($s['title']).'</td><td>'.$this->lang('%d partie|%d parties', $s['parts'], $s['parts']).'</td><td class="text-end">'
				.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/articles/series/'.$s['series_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'">'.icon('fas fa-pen').'</a> '
				.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/articles/series/delete/'.$s['series_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) $this->lang('Supprimer cette série ? Ses billets restent publiés.'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'">'.icon('far fa-trash-alt').'</a>'
				.'</td></tr>';
		}

		$corps = $lignes
			? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>'.$this->lang('Série').'</th><th>'.$this->lang('Parties').'</th><th></th></tr></thead><tbody>'.$lignes.'</tbody></table></div>'
			: $this->admin_empty('fas fa-layer-group', $this->lang('Aucune série pour le moment.'), $this->lang('Une série réunit un billet en plusieurs parties : chaque partie affiche la liste des autres, dans l’ordre.'));

		return $this->admin_back('admin/articles').$this->admin_card('fas fa-layer-group', $this->lang('Séries'), $corps, '', '<a class="btn btn-primary btn-sm" href="'.url('admin/articles/series/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle série').'</a>');
	}

	public function _series_add()
	{
		return $this->_formulaire_serie(NULL);
	}

	public function _series_edit($serie)
	{
		return $this->_formulaire_serie($serie);
	}

	public function _series_delete($serie)
	{
		$this->check_csrf('admin/articles/series');

		$this->_modele()->delete_series((int) $serie['series_id']);

		notify($this->lang('Série supprimée.'));
		redirect('admin/articles/series');
	}

	private function _formulaire_serie(?array $serie)
	{
		$titre = $serie ? $this->lang('Éditer la série : %s', $serie['title']) : $this->lang('Nouvelle série');

		$this->title($titre)->icon('fas fa-layer-group')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $serie['title'] ?? '', 'rules' => 'required'],
				'description' => ['label' => $this->lang('Présentation'), 'type' => 'textarea', 'value' => $serie['description'] ?? '',
				                  'description' => $this->lang('Affichée en tête de la page de la série.')]
			 ])
			 ->add_submit($serie ? $this->lang('Enregistrer') : $this->lang('Créer'), $serie ? 'fas fa-check' : 'fas fa-plus')
			 ->add_back('admin/articles/series');

		if ($this->form()->is_valid($post))
		{
			$this->_modele()->save_series($serie ? (int) $serie['series_id'] : NULL, $this->config->lang->info()->name, trim((string) $post['title']), trim((string) ($post['description'] ?? '')));

			notify($serie ? $this->lang('Série modifiée.') : $this->lang('Série créée.'));
			redirect('admin/articles/series');
		}

		return $this->admin_card('fas fa-layer-group', $titre, $this->form()->display());
	}

	/** Le modèle du Blog, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Articles\Models\Articles
	{
		$modele = $this->model('articles');

		if (!$modele instanceof \NF\Modules\Articles\Models\Articles)
		{
			throw new \LogicException('modèle du Blog introuvable');
		}

		return $modele;
	}

	public function _categories_delete($category)
	{
		$this->check_csrf('admin/articles/categories');

		// Vérifier qu'il n'y a pas d'articles dans cette catégorie
		$count = (int)NeoFrag()->db	->select('COUNT(*)')
									->from('nf_articles')
									->where('category_id', $category['category_id'])
									->row();

		if ($count > 0)
		{
			notify($this->lang('Impossible : %d article(s) dans cette catégorie. Déplace-les ou supprime-les d\'abord.', $count));
			redirect('admin/articles/categories');
		}

		NeoFrag()->db	->where('category_id', $category['category_id'])
						->delete('nf_articles_categories');
		NeoFrag()->db	->where('category_id', $category['category_id'])
						->delete('nf_articles_categories_lang');

		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/articles/categories');
	}
}
