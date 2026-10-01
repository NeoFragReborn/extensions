<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		$categories = [];
		foreach ($this->model()->get_categories() as $c)
		{
			$categories[(int)$c['category_id']] = $c['title'];
		}

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($categories[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$articles = array_values(array_filter($this->model()->get_articles(), function($a) use ($filters)
		{
			if ($filters['q'] !== '' && stripos((string)$a['title'], $filters['q']) === FALSE && stripos((string)$a['excerpt'], $filters['q']) === FALSE)
			{
				return FALSE;
			}
			if ($filters['category'] && (int)$a['category_id'] !== $filters['category'])
			{
				return FALSE;
			}
			if ($filters['status'] === 'published' && empty($a['published']))
			{
				return FALSE;
			}
			if ($filters['status'] === 'draft' && !empty($a['published']))
			{
				return FALSE;
			}
			return TRUE;
		}));

		$published = 0;
		foreach ($articles as $a)
		{
			if (!empty($a['published'])) $published++;
		}

		$filters['categories'] = $categories;
		$filters['matched']    = count($articles);
		$filters['published']  = $published;
		$filters['drafts']     = count($articles) - $published;
		$filters['active']     = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		// Tri (collection complète, AVANT pagination) — colonnes en allowlist.
		$filters['sort_cols'] = [
			'date'  => $this->lang('Date'),
			'title' => $this->lang('Titre'),
			'views' => $this->lang('Vues'),
		];
		list($articles, $filters['sort']) = $this->sort_items($articles, [
			'date'  => 'date',
			'title' => 'title',
			'views' => 'views',
		], 'date', 'desc');

		return [
			$this->module->pagination->fix_items_per_page($this->config->articles_per_page ?: 10)->get_data($articles, $page),
			$filters
		];
	}

	public function _add()
	{
		if (!$this->is_authorized('add_articles'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _delete($article_id, $title)
	{
		if (!$this->is_authorized('delete_articles'))
		{
			$this->error->unauthorized();
		}

		if (($article = $this->model()->get_article($article_id)) && !empty($article))
		{
			return [$article];
		}
	}

	public function _edit($article_id, $title)
	{
		if (!$this->is_authorized('modify_articles'))
		{
			$this->error->unauthorized();
		}

		if (($article = $this->model()->get_article($article_id)) && !empty($article))
		{
			return [$article];
		}
	}

	public function _history($article_id, $title)
	{
		if (!$this->is_authorized('modify_articles'))
		{
			$this->error->unauthorized();
		}

		if (($article = $this->model()->get_article($article_id)) && !empty($article))
		{
			return [$article];
		}
	}

	public function _revision_restore($article_id, $title, $revision_id)
	{
		if (!$this->is_authorized('modify_articles'))
		{
			$this->error->unauthorized();
		}

		if (($article = $this->model()->get_article($article_id)) && !empty($article))
		{
			return [$article, (int)$revision_id];
		}
	}

	public function _categories_add()
	{
		if (!$this->is_authorized('add_categories'))
		{
			$this->error->unauthorized();
		}

		return ['add'];
	}

	public function _categories_edit($category_id, $title)
	{
		if (!$this->is_authorized('modify_categories'))
		{
			$this->error->unauthorized();
		}

		if (($category = NeoFrag()->db	->select('c.*', 'cl.title')
										->from('nf_articles_categories c')
										->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id')
										->where('c.category_id', $category_id)
										->row()))
		{
			return [$category];
		}
	}

	public function _categories_delete($category_id, $title)
	{
		if (!$this->is_authorized('delete_categories'))
		{
			$this->error->unauthorized();
		}

		if (($category = NeoFrag()->db	->select('c.*', 'cl.title')
										->from('nf_articles_categories c')
										->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id')
										->where('c.category_id', $category_id)
										->row()))
		{
			return [$category];
		}
	}

	/** La liste des catégories : on y retrouve chacune pour la modifier ou la supprimer. */
	public function _categories()
	{
		if (!$this->is_authorized('add_categories') && !$this->is_authorized('modify_categories') && !$this->is_authorized('delete_categories'))
		{
			$this->error->unauthorized();
		}

		return [$this->_modele()->get_categories()];
	}

	/*
	 * Les séries s'organisent avec les billets : elles demandent le droit de modifier
	 * les billets.
	 */

	public function _series()
	{
		$this->_droit_series();

		return [$this->_modele()->get_series_list()];
	}

	public function _series_add()
	{
		$this->_droit_series();

		return [];
	}

	public function _series_edit($series_id, $title)
	{
		$this->_droit_series();

		return ($serie = $this->_modele()->get_series((int) $series_id)) ? [$serie] : NULL;
	}

	public function _series_delete($series_id, $title)
	{
		$this->_droit_series();

		return ($serie = $this->_modele()->get_series((int) $series_id)) ? [$serie] : NULL;
	}

	private function _droit_series(): void
	{
		if (!$this->is_authorized('modify_articles'))
		{
			$this->error->unauthorized();
		}
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
}
