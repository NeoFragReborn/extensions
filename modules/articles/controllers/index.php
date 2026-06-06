<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Articles\Articles;

class Index extends Controller_Module
{
	public function index($articles)
	{
		$this	->title($this->lang('Articles'))
				->icon('far fa-newspaper')
				->breadcrumb();

		return $this->panel()
					->heading()
					->body($this->view('list', ['articles' => $articles]));
	}

	public function _article($article)
	{
		$this	->title($article['title'])
				->meta_description(!empty($article['excerpt']) ? $article['excerpt'] : $article['content'])
				->icon('far fa-newspaper')
				->breadcrumb($article['category_title'], 'articles/category/'.$article['category_id'].'/'.url_title($article['category_name']))
				->breadcrumb($article['title']);

		// Si Markdown détecté, convertit en HTML d'abord (puis build TOC qui scanne <h2>/<h3>)
		$content = render_content($article['content']);
		$toc = Articles::build_toc($content);
		$read_time = Articles::read_time_minutes($content);

		return $this->panel()
					->heading()
					->body($this->view('article', [
						'article'   => $article,
						'content'   => $content,
						'toc'       => $toc,
						'read_time' => $read_time
					]));
	}

	public function _category($articles, $category_id, $title)
	{
		$this	->title($title)
				->icon('far fa-folder-open')
				->breadcrumb();

		$follow = ($notifications = $this->module('notifications')) ? '<div class="mb-3">'.$notifications->follow_button('article-category', (int)$category_id).'</div>' : '';

		return $this->panel()
					->heading()
					->body($follow.$this->view('list', ['articles' => $articles]));
	}

	public function _tag($articles, $tag)
	{
		$this	->title('#'.$tag)
				->icon('fas fa-tag')
				->breadcrumb();

		return $this->panel()
					->heading()
					->body($this->view('list', ['articles' => $articles]));
	}
}
