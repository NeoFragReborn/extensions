<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module->pagination
								->fix_items_per_page($this->config->articles_per_page ?: 10)
								->get_data($this->model()->get_articles(), $page)];
	}

	public function _article($article_id, $title)
	{
		if (($article = $this->model()->get_article($article_id)) && !empty($article))
		{
			if (count_view('article', $article_id))
			{
				$this->model()->increment_views($article_id);
			}
			return [$article];
		}
	}

	public function _category($category_id, $title, $page = '')
	{
		$articles = $this->model()->get_articles('category', $category_id);

		if (empty($articles))
		{
			return;
		}

		return [
			$this->module->pagination
							->fix_items_per_page($this->config->articles_per_page ?: 10)
							->get_data($articles, $page),
			$category_id,
			$title
		];
	}

	public function _tag($tag, $page = '')
	{
		$articles = $this->model()->get_articles('tag', $tag);

		return [
			$this->module->pagination
							->fix_items_per_page($this->config->articles_per_page ?: 10)
							->get_data($articles, $page),
			$tag
		];
	}
}
