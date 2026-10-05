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

	public function _article($article, $autour = [], $serie = [])
	{
		// Le titre et la description que le billet donne aux moteurs, s'il en donne.
		nf_seo_contenu('articles', (int) $article['article_id']);

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

		$this->_partage($article, $content);

		return $this->panel()
					->style('card-transparent blog-panel')
					->body($this->view('article', [
						'article'   => $article,
						'content'   => $content,
						'toc'       => $toc,
						'read_time' => $read_time,
						'autour'    => $autour + ['precedent' => NULL, 'suivant' => NULL, 'lies' => []],
						'serie'     => $serie,
						'fiche'     => Articles::mise_en_page('fiche', (string) $this->config->articles_fiche),
					]), FALSE);
	}

	public function _auteur($articles, $auteur, $barre = [], $pagination = '')
	{
		$this	->title($this->lang('Billets de %s', $auteur['username']))
				->icon('fas fa-user-edit')
				->breadcrumb();

		$membre = $this->module('user')->model2('user', $auteur['user_id']);

		$entete = '<header class="blog-page-entete">'
			.($membre instanceof \NF\NeoFrag\Models\User ? $membre->avatar()->append_attr('class', 'blog-avatar-grand') : '')
			.'<div><h1>'.nf_texte($auteur['username']).'</h1><p>'.$this->lang('%d billet publié|%d billets publiés', $auteur['total'], $auteur['total'])
			// Pas user->link() : son deuxième argument est le NOM du membre, qui fait aussi l'adresse — le
			// libellé y donnait /user/<id>/voir-son-profil, une page introuvable (check-liens, 2026-10-03).
			.' · <a href="'.url('user/'.(int) $auteur['user_id'].'/'.url_title($auteur['username'])).'">'.$this->lang('Voir son profil').'</a></p></div></header>';

		return $this->_liste($articles, NULL, $barre, $pagination, $entete);
	}

	public function _archives($articles, $mois, $barre = [], $pagination = '')
	{
		$libelle = timetostr('F Y', $mois.'-01');

		$this	->title($this->lang('Archives : %s', $libelle))
				->icon('far fa-calendar-alt')
				->breadcrumb();

		return $this->_liste($articles, NULL, $barre, $pagination, '<header class="blog-page-entete"><div><h1>'.nf_texte($libelle).'</h1><p>'.$this->lang('%d billet publié|%d billets publiés', count($articles), count($articles)).'</p></div></header>', 0, $mois);
	}

	public function _serie($serie, $parties)
	{
		$this->css('blog');
		$this->js('blog');

		$this	->title($serie['title'])
				->meta_description($serie['description'] ?: $serie['title'])
				->icon('fas fa-layer-group')
				->breadcrumb();

		return $this->panel()
					->style('card-transparent blog-panel')
					->body($this->view('serie', [
						'serie'   => $serie,
						'parties' => $parties,
					]), FALSE);
	}

	/**
	 * Ce que voit un réseau social ou un moteur quand on partage un billet : sa
	 * couverture, le type « article », et ses données structurées (titre, auteur, dates, image).
	 */
	private function _partage(array $article, string $content): void
	{
		$image   = !empty($article['image']) ? (string) NeoFrag()->model2('file', $article['image'])->path() : '';
		// `path()` rend une adresse depuis la racine : on lui ajoute l'origine (pas `absolute_url()`,
		// qui y glisserait la langue comme pour une page).
		$image   = $image !== '' && strpos($image, '://') === FALSE ? site_origin().'/'.ltrim($image, '/') : $image;
		$adresse = absolute_url('articles/'.$article['article_id'].'/'.url_title($article['title']));

		$donnees = array_filter([
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'headline'         => mb_substr(nf_texte_brut($article['title']), 0, 110),
			'description'      => mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', nf_texte_brut(strip_tags(!empty($article['excerpt']) ? (string) $article['excerpt'] : $content)))), 0, 300, '…'),
			'datePublished'    => date('c', (int) strtotime((string) $article['date'])),
			'author'           => !empty($article['username']) ? ['@type' => 'Person', 'name' => nf_texte_brut($article['username'])] : NULL,
			'image'            => $image ?: NULL,
			'mainEntityOfPage' => $adresse,
			'wordCount'        => count(preg_split('/\s+/', strip_tags($content), -1, PREG_SPLIT_NO_EMPTY) ?: []),
		], static fn ($v) => $v !== NULL && $v !== '');

		$this->output->data->set('module', 'og_type', 'article');
		$this->output->data->set('module', 'jsonld', $donnees);

		if ($image !== '')
		{
			$this->output->data->set('module', 'og_image', $image);
		}
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

	private function _liste($articles, $une, $barre, $pagination, string $avant = '', int $categorie = 0, string $mois = '')
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
						'mois'       => $mois,
						'liste'      => Articles::mise_en_page('liste', (string) $this->config->articles_liste),
						'pagination' => (string) $pagination,
					]), FALSE);
	}
}
