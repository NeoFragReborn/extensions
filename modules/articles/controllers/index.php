<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Articles\Articles;

class Index extends Controller_Module
{
	/*
	 * Le Blog. Deux réglages du module choisissent la mise en page — la liste, la fiche —
	 * et le visiteur peut, sur la liste, passer de la grille aux lignes (js/blog.js le retient).
	 */

	public function index($articles, $une = NULL, $barre = [], $pagination = '')
	{
		$this	->title($this->lang('Blog'))
				->icon('far fa-newspaper')
				->breadcrumb();

		return $this->_liste($articles, $une, $barre, $pagination);
	}

	public function _article($article, $autour = [])
	{
		$this->css('blog');
		$this->js('blog');

		$this	->title($article['title'])
				->meta_description(!empty($article['excerpt']) ? $article['excerpt'] : $article['content'])
				->icon('far fa-newspaper')
				->breadcrumb($article['category_title'], 'articles/category/'.$article['category_id'].'/'.url_title($article['category_name']))
				->breadcrumb($article['title']);

		// Si Markdown détecté, convertit en HTML d'abord (puis build TOC qui scanne <h2>/<h3>)
		$content   = render_content($article['content']);
		$toc       = Articles::build_toc($content);
		$read_time = Articles::read_time_minutes($content);

		return $this->panel()
					->style('card-transparent blog-panel')
					->body($this->view('article', [
						'article'   => $article,
						'content'   => $content,
						'toc'       => $toc,
						'read_time' => $read_time,
						'autour'    => $autour + ['precedent' => NULL, 'suivant' => NULL, 'lies' => []],
						'fiche'     => Articles::mise_en_page('fiche', (string) $this->config->articles_fiche),
					]), FALSE);
	}

	public function _category($articles, $category_id, $title, $barre = [], $pagination = '')
	{
		$this	->title($title)
				->icon('far fa-folder-open')
				->breadcrumb();

		$follow = ($notifications = $this->module('notifications')) ? '<div class="mb-3">'.$notifications->follow_button('article-category', (int)$category_id).'</div>' : '';

		return $this->_liste($articles, NULL, $barre, $pagination, $follow, (int) $category_id);
	}

	public function _tag($articles, $tag, $barre = [], $pagination = '')
	{
		$this	->title('#'.$tag)
				->icon('fas fa-tag')
				->breadcrumb();

		return $this->_liste($articles, NULL, $barre, $pagination);
	}

	private function _liste($articles, $une, $barre, $pagination, string $avant = '', int $categorie = 0)
	{
		$this->css('blog');
		$this->js('blog');

		return $this->panel()
					->style('card-transparent blog-panel')
					->body($avant.$this->view('list', [
						'articles'   => $articles,
						'une'        => $une,
						'barre'      => $barre,
						'categorie'  => $categorie,
						'liste'      => Articles::mise_en_page('liste', (string) $this->config->articles_liste),
						'pagination' => (string) $pagination,
					]), FALSE);
	}
}
