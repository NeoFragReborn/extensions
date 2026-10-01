<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Models;

use NF\NeoFrag\Loadables\Model;

class Articles extends Model
{
	// Back-end de parution programmée partagé avec le module news (announce / publish_scheduled /
	// increment_views). La présentation reste propre à articles (blog long : sommaire, temps de lecture).
	use \NF\NeoFrag\Traits\Publishable_Content;

	protected function publishable_config(): array
	{
		return [
			'table'         => 'nf_articles',
			'id'            => 'article_id',
			'lang_table'    => 'nf_articles_lang',
			'type'          => 'article',
			'url_prefix'    => 'articles',
			'notif_message' => 'Nouvel article : %s',
		];
	}

	public function get_articles($filter = '', $filter_data = '')
	{
		/*
		 * La langue d'une LISTE est celle qu'on demande — sauf pour la page d'une catégorie, qui
		 * est une page de contenu comme une autre. Si la catégorie n'existe que dans une langue,
		 * la lister dans une autre rendait une liste vide, donc un 404 pour le visiteur. On sert
		 * alors la langue de la catégorie, et la page le dit.
		 */
		$lang = $filter == 'category' && !empty($filter_data)
			? $this->langue_du_contenu('nf_articles_categories_lang', 'category_id', $filter_data)
			: $this->config->lang->info()->name;

		$this->db	->select('a.*', 'al.title', 'al.excerpt', 'al.content', 'al.tags',
							'IFNULL(a.image_id, c.image_id) as image',
							'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title',
							'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
					->from('nf_articles a')
					->join('nf_articles_lang al',            'a.article_id  = al.article_id')
					->join('nf_articles_categories c',       'a.category_id = c.category_id')
					->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id', $lang)
					->join('nf_user u',                      'a.user_id     = u.id AND u.deleted = "0"')
					->join('nf_user_profile up',             'up.id         = u.id')
					->where('al.lang', $lang)
					->where('a.deleted_at', NULL)
					->order_by('a.date DESC');

		if (!empty($filter) && !empty($filter_data))
		{
			if ($filter == 'tag')
			{
				$this->db->where('al.tags FIND_IN_SET', $filter_data);
			}
			else if ($filter == 'category')
			{
				$this->db->where('a.category_id', $filter_data);
			}
			else if ($filter == 'user')
			{
				$this->db->where('a.user_id', (int) $filter_data);
			}
			else if ($filter == 'month' && preg_match('/^\d{4}-\d{2}$/', (string) $filter_data))
			{
				// Un mois d'archives : du premier jour inclus au premier du mois suivant exclu.
				$debut = $filter_data.'-01 00:00:00';
				$this->db->where('a.date >=', $debut)->where('a.date <', date('Y-m-d H:i:s', (int) strtotime($debut.' +1 month')));
			}
			else if ($filter == 'series')
			{
				$this->db->where('a.series_id', (int) $filter_data);
			}
		}

		if (!$this->url->admin)
		{
			// Publication programmée : une date future masque l'article jusqu'à son heure.
			$this->db->where('a.published', TRUE)->where('a.date <=', date('Y-m-d H:i:s'));
		}

		return $this->db->get();
	}

	public function get_article($article_id)
	{
		// Résolu AVANT la requête : `$this->db` est un constructeur partagé, et l'interroger au
		// milieu d'une chaîne écrase celle qu'on est en train de bâtir.
		// La catégorie suit la langue de l'article : elles sont saisies ensemble, et un article
		// servi en repli doit porter le libellé de catégorie de SA version.
		$lang = $this->langue_du_contenu('nf_articles_lang', 'article_id', $article_id);

		$article = $this->db	->select('a.*', 'al.title', 'al.excerpt', 'al.content', 'al.tags',
										'IFNULL(a.image_id, c.image_id) as image',
										'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title',
										'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
								->from('nf_articles a')
								->join('nf_articles_lang al',            'a.article_id  = al.article_id')
								->join('nf_articles_categories c',       'a.category_id = c.category_id')
								->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id', $lang)
								->join('nf_user u',                      'a.user_id     = u.id AND u.deleted = "0"')
								->join('nf_user_profile up',             'up.id         = u.id')
								->where('a.article_id', $article_id)
								->where('al.lang', $lang)
								->where('a.deleted_at', NULL)
								->row();

		// Hors admin : masque un article non publié OU programmé (date de publication future).
		if ($article && !$this->url->admin && (!$article['published'] || strtotime($article['date']) > time()))
		{
			return [];
		}

		return $article;
	}

	public function restore_article($article_id)
	{
		$this->db	->where('article_id', (int)$article_id)
					->update('nf_articles', 'deleted_at = NULL, deleted_by = NULL');
	}

	// Purge : suppression définitive (lignes + lang + commentaires associés).
	public function purge_article($article_id)
	{
		$article_id = (int)$article_id;

		if ($comments = $this->module('comments'))
		{
			$comments->delete('articles', $article_id);
		}

		$this->db->where('article_id', $article_id)->delete('nf_articles');
		$this->db->where('article_id', $article_id)->delete('nf_articles_lang');
	}

	/**
	 * Une série du Blog, son titre et sa présentation dans la langue affichée — ou, à
	 * défaut, dans celle où elle a été écrite.
	 *
	 * @return array{series_id: int, title: string, description: string}|null
	 */
	public function get_series(int $series_id): ?array
	{
		$serie = $this->db	->select('s.series_id', 'sl.title', 'sl.description')
							->from('nf_articles_series s')
							->join_lang('nf_articles_series_lang sl', 'series_id', 's.series_id')
							->where('s.series_id', $series_id)
							->row();

		return is_array($serie) && $serie ? ['series_id' => (int) $serie['series_id'], 'title' => (string) $serie['title'], 'description' => (string) $serie['description']] : NULL;
	}

	/**
	 * Toutes les séries, avec leur nombre de parties (brouillons compris : c'est la liste de
	 * l'administration).
	 *
	 * @return array<int, array{series_id: int, title: string, description: string, parts: int}>
	 */
	public function get_series_list(): array
	{
		$series = [];

		foreach ($this->db	->select('s.series_id', 'sl.title', 'sl.description', 'COUNT(a.article_id) AS parts')
							->from('nf_articles_series s')
							->join_lang('nf_articles_series_lang sl', 'series_id', 's.series_id')
							->join('nf_articles a', 'a.series_id = s.series_id AND a.deleted_at IS NULL', 'LEFT')
							->group_by('s.series_id')
							->order_by('sl.title')
							->get() as $s)
		{
			$series[(int) $s['series_id']] = ['series_id' => (int) $s['series_id'], 'title' => (string) $s['title'], 'description' => (string) $s['description'], 'parts' => (int) $s['parts']];
		}

		return $series;
	}

	/** Enregistre une série (création si $series_id est NULL) : son titre et sa présentation dans $lang. */
	public function save_series(?int $series_id, string $lang, string $title, string $description): int
	{
		if ($series_id === NULL)
		{
			$series_id = (int) $this->db->insert('nf_articles_series', ['series_id' => NULL]);
		}

		$this->db->where('series_id', $series_id)->where('lang', $lang)->delete('nf_articles_series_lang');
		$this->db->insert('nf_articles_series_lang', ['series_id' => $series_id, 'lang' => $lang, 'title' => $title, 'description' => $description]);

		return $series_id;
	}

	/** Supprime une série : ses billets restent, ils n'en font simplement plus partie. */
	public function delete_series(int $series_id): void
	{
		$this->db->where('series_id', $series_id)->update('nf_articles', ['series_id' => NULL, 'series_order' => 0]);
		$this->db->where('series_id', $series_id)->delete('nf_articles_series');
	}

	public function get_categories()
	{
		return $this->db	->select('c.category_id', 'c.name', 'cl.title', 'COUNT(a.article_id) AS articles_count')
							->from('nf_articles_categories c')
							->join_lang('nf_articles_categories_lang cl', 'category_id', 'c.category_id')
							->join('nf_articles a',                  'c.category_id = a.category_id AND a.deleted_at IS NULL', 'LEFT')
							->group_by('c.category_id')
							->order_by('cl.title')
							->get();
	}
}
