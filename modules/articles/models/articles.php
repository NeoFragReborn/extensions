<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Models;

use NF\NeoFrag\Loadables\Model;

class Articles extends Model
{
	public function get_articles($filter = '', $filter_data = '')
	{
		$this->db	->select('a.*', 'al.title', 'al.excerpt', 'al.content', 'al.tags',
							'IFNULL(a.image_id, c.image_id) as image',
							'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title',
							'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
					->from('nf_articles a')
					->join('nf_articles_lang al',            'a.article_id  = al.article_id')
					->join('nf_articles_categories c',       'a.category_id = c.category_id')
					->join('nf_articles_categories_lang cl', 'c.category_id = cl.category_id')
					->join('nf_user u',                      'a.user_id     = u.id AND u.deleted = "0"')
					->join('nf_user_profile up',             'up.id         = u.id')
					->where('al.lang', $this->config->lang->info()->name)
					->where('cl.lang', $this->config->lang->info()->name)
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
		$article = $this->db	->select('a.*', 'al.title', 'al.excerpt', 'al.content', 'al.tags',
										'IFNULL(a.image_id, c.image_id) as image',
										'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title',
										'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
								->from('nf_articles a')
								->join('nf_articles_lang al',            'a.article_id  = al.article_id')
								->join('nf_articles_categories c',       'a.category_id = c.category_id')
								->join('nf_articles_categories_lang cl', 'c.category_id = cl.category_id')
								->join('nf_user u',                      'a.user_id     = u.id AND u.deleted = "0"')
								->join('nf_user_profile up',             'up.id         = u.id')
								->where('a.article_id', $article_id)
								->where('al.lang', $this->config->lang->info()->name)
								->where('cl.lang', $this->config->lang->info()->name)
								->where('a.deleted_at', NULL)
								->row();

		// Hors admin : masque un article non publié OU programmé (date de publication future).
		if ($article && !$this->url->admin && (!$article['published'] || strtotime($article['date']) > time()))
		{
			return [];
		}

		return $article;
	}

	public function increment_views($article_id)
	{
		$this->db->execute('UPDATE nf_articles SET views = views + 1 WHERE article_id = '.(int)$article_id);
	}

	/**
	 * Parution effective d'un article : émet event + webhook + gamification + notifications
	 * UNE SEULE FOIS, au moment réel de parution. Idempotent via announced_at. Appelée à
	 * l'enregistrement (parution immédiate) ET par l'endpoint de parution (cron) pour le
	 * contenu programmé arrivé à échéance.
	 *
	 * @return bool TRUE si l'annonce vient d'être émise.
	 */
	public function announce($article_id)
	{
		$article_id = (int)$article_id;

		$row = $this->db	->select('a.user_id', 'a.category_id', 'a.date', 'a.published', 'a.announced_at', 'al.title')
							->from('nf_articles a')
							->join('nf_articles_lang al', 'a.article_id = al.article_id')
							->where('a.article_id', $article_id)
							->where('al.lang', $this->config->lang->info()->name)
							->where('a.deleted_at', NULL)
							->row();

		if (!$row || $row['published'] != '1' || !empty($row['announced_at']) || strtotime($row['date']) > time())
		{
			return FALSE;
		}

		// Marque AVANT d'émettre : empêche toute double émission (ré-entrance / passages cron concurrents).
		$this->db->where('article_id', $article_id)->update('nf_articles', ['announced_at' => date('Y-m-d H:i:s')]);

		$title = (string)$row['title'];
		$url   = 'articles/'.$article_id.'/'.url_title($title);
		$owner = (int)$row['user_id'];

		$this->events->fire('article.published', ['article_id' => $article_id, 'title' => $title]);

		if ($gam = $this->module('gamification'))
		{
			// Barème 'news' = « news / article publié » (clé partagée, cf. admin gamification).
			if ($gam_owner = $gam->content_owner('article', $article_id))
			{
				$gam->earn($gam_owner, 'news');
				$gam->recompute($gam_owner);
			}
		}

		if ($wh = $this->module('webhooks'))
		{
			$wh->trigger('article.published', ['article_id' => $article_id, 'title' => $title, 'url' => url($url)]);
		}

		if ($notifications = $this->module('notifications'))
		{
			foreach ($notifications->subscribers('article-category', (int)$row['category_id'], $owner) as $uid)
			{
				$notifications->push($uid, 'article', $this->lang('Nouvel article : %s', $title), $url, $owner);
			}
		}

		return TRUE;
	}

	/** Parution des articles programmés arrivés à échéance (appelée par l'endpoint cron). @return int annoncés */
	public function publish_scheduled()
	{
		$count = 0;

		foreach ($this->db	->select('article_id')
							->from('nf_articles')
							->where('published', '1')
							->where('announced_at', NULL)
							->where('date <=', date('Y-m-d H:i:s'))
							->where('deleted_at', NULL)
							->get() as $article_id)
		{
			if ($this->announce($article_id))
			{
				$count++;
			}
		}

		return $count;
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

	public function get_categories()
	{
		return $this->db	->select('c.category_id', 'c.name', 'cl.title', 'COUNT(a.article_id) AS articles_count')
							->from('nf_articles_categories c')
							->join('nf_articles_categories_lang cl', 'c.category_id = cl.category_id')
							->join('nf_articles a',                  'c.category_id = a.category_id AND a.deleted_at IS NULL', 'LEFT')
							->where('cl.lang', $this->config->lang->info()->name)
							->group_by('c.category_id')
							->order_by('cl.title')
							->get();
	}
}
