<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Articles\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	/*
	 * Le Blog (2026-10-01). La liste, la barre latérale et le billet à la une se calculent
	 * ensemble, à partir de la même liste des billets VISIBLES dans la langue affichée : un compteur ou
	 * une archive ne peut ainsi jamais annoncer un billet que la liste ne montre pas (brouillon,
	 * programmé, à la corbeille, d'une autre langue).
	 */

	public function index($page = '')
	{
		$tous = $this->_modele()->get_articles();

		// À la une : le plus récent marqué « à la une », sinon le plus récent tout court. Il ouvre la
		// première page, et n'est pas répété dans la grille.
		$une = NULL;

		foreach ($tous as $billet)
		{
			if (!empty($billet['featured']))
			{
				$une = $billet;
				break;
			}
		}

		$une  ??= $tous[0] ?? NULL;
		$reste = $une ? array_values(array_filter($tous, static fn (array $b): bool => (int) $b['article_id'] !== (int) $une['article_id'])) : $tous;

		[$donnees, $pagination] = $this->_paginer($reste, $page);

		return [
			$donnees,
			$this->_premiere_page($page) ? $une : NULL,
			$this->_barre($tous),
			$pagination,
		];
	}

	public function _article($article_id, $title)
	{
		if (($article = $this->_modele()->get_article($article_id)))
		{
			// Le titre de l'adresse n'est pas celui de la langue servie : 301 vers la bonne (elle répondait 200 à n'importe
			// lequel, canonique sur l'adresse fautive).
			nf_bon_titre((string) $title, (string) $article['title'], 'articles/'.(int) $article_id);

			if (count_view('article', $article_id))
			{
				$this->_modele()->increment_views($article_id);
			}

			return [$article, $this->_autour($article), $this->_serie_du_billet($article)];
		}
	}

	/** La page d'un auteur : ses billets visibles. Un auteur sans billet visible n'a pas de page. */
	public function _auteur($user_id, $title, $page = '')
	{
		$articles = $this->_modele()->get_articles('user', (int) $user_id);

		if (empty($articles))
		{
			return;
		}

		nf_bon_titre((string) $title, (string) $articles[0]['username'], 'articles/auteur/'.(int) $user_id, (string) $page);

		[$donnees, $pagination] = $this->_paginer($articles, $page);

		return [
			$donnees,
			['user_id' => (int) $user_id, 'username' => (string) $articles[0]['username'], 'total' => count($articles)],
			$this->_barre($this->_modele()->get_articles()),
			$pagination,
		];
	}

	/** Un mois d'archives. Un mois impossible ou sans billet visible n'a pas de page. */
	public function _archives($annee, $mois, $page = '')
	{
		$annee = (int) $annee;
		$mois  = (int) $mois;

		if ($annee < 1970 || $mois < 1 || $mois > 12)
		{
			return;
		}

		$cle      = sprintf('%04d-%02d', $annee, $mois);
		$articles = $this->_modele()->get_articles('month', $cle);

		if (empty($articles))
		{
			return;
		}

		[$donnees, $pagination] = $this->_paginer($articles, $page);

		return [
			$donnees,
			$cle,
			$this->_barre($this->_modele()->get_articles()),
			$pagination,
		];
	}

	/** La page d'une série : sa présentation, puis ses parties visibles dans l'ordre. */
	public function _serie($series_id, $title)
	{
		if (!($serie = $this->_modele()->get_series((int) $series_id)) || !($parties = $this->_parties((int) $series_id)))
		{
			return;
		}

		nf_bon_titre((string) $title, (string) $serie['title'], 'articles/serie/'.(int) $series_id);

		return [$serie, $parties];
	}

	/**
	 * Les parties visibles d'une série, dans leur ordre : le rang, puis la date pour départager.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function _parties(int $series_id): array
	{
		$parties = $this->_modele()->get_articles('series', $series_id);

		usort($parties, static fn (array $a, array $b): int => [(int) $a['series_order'], (string) $a['date']] <=> [(int) $b['series_order'], (string) $b['date']]);

		return $parties;
	}

	/** La série d'un billet et ses parties, ou un tableau vide s'il n'en fait pas partie. */
	private function _serie_du_billet(array $article): array
	{
		if (empty($article['series_id']) || !($serie = $this->_modele()->get_series((int) $article['series_id'])))
		{
			return [];
		}

		return ['serie' => $serie, 'parties' => $this->_parties((int) $article['series_id'])];
	}

	public function _category($category_id, $title, $page = '')
	{
		$articles = $this->_modele()->get_articles('category', $category_id);

		if (empty($articles))
		{
			return;
		}

		nf_bon_titre((string) $title, (string) $articles[0]['category_name'], 'articles/category/'.(int) $category_id, (string) $page);

		[$donnees, $pagination] = $this->_paginer($articles, $page);

		// Le titre de la catégorie dans la langue servie : la page prenait celui de l'adresse (« mises-a-jour »).
		return [
			$donnees,
			$category_id,
			(string) $articles[0]['category_title'],
			$this->_barre($this->_modele()->get_articles()),
			$pagination,
		];
	}

	public function _tag($tag, $page = '')
	{
		$articles = $this->_modele()->get_articles('tag', $tag);

		[$donnees, $pagination] = $this->_paginer($articles, $page);

		return [
			$donnees,
			$tag,
			$this->_barre($this->_modele()->get_articles()),
			$pagination,
		];
	}

	/**
	 * Une page de billets et sa pagination, dans cet ordre : la pagination se calcule sur la page
	 * qu'on vient de découper.
	 *
	 * @param array<int, array<string, mixed>> $billets
	 * @return array{0: mixed, 1: string}
	 */
	private function _paginer(array $billets, $page): array
	{
		$pages   = $this->module->pagination;
		$donnees = $pages->fix_items_per_page($this->config->articles_per_page ?: 10)->get_data($billets, $page);

		return [$donnees, (string) $pages->get_pagination()];
	}

	private function _premiere_page($page): bool
	{
		return !preg_match('/(\d+)/', (string) $page, $m) || (int) $m[1] <= 1;
	}

	/**
	 * La barre latérale du Blog : catégories et leur nombre de billets, les plus lus, les tags, les
	 * archives par mois.
	 *
	 * @param array<int, array<string, mixed>> $tous
	 */
	private function _barre(array $tous): array
	{
		$categories = $tags = $archives = [];

		foreach ($tous as $billet)
		{
			$id = (int) $billet['category_id'];
			$categories[$id] ??= ['category_id' => $id, 'name' => $billet['category_name'], 'title' => $billet['category_title'], 'total' => 0];
			$categories[$id]['total']++;

			foreach (array_filter(array_map('trim', explode(',', (string) $billet['tags']))) as $tag)
			{
				$tags[$tag] = ($tags[$tag] ?? 0) + 1;
			}

			$mois = substr((string) $billet['date'], 0, 7);
			$archives[$mois] = ($archives[$mois] ?? 0) + 1;
		}

		$populaires = $tous;
		usort($populaires, static fn (array $a, array $b): int => (int) $b['views'] <=> (int) $a['views']);

		arsort($tags);
		krsort($archives);
		usort($categories, static fn (array $a, array $b): int => strcmp((string) $a['title'], (string) $b['title']));

		return [
			'categories' => $categories,
			'populaires' => array_slice($populaires, 0, 5),
			'tags'       => array_slice($tags, 0, 15, TRUE),
			'archives'   => array_slice($archives, 0, 12, TRUE),
		];
	}

	/**
	 * Autour d'un billet : le précédent et le suivant (par date), et trois billets à lire aussi — de
	 * la même catégorie d'abord.
	 */
	private function _autour(array $article): array
	{
		$tous = $this->_modele()->get_articles();
		$ids  = array_map(static fn (array $b): int => (int) $b['article_id'], $tous);
		$rang = array_search((int) $article['article_id'], $ids, TRUE);

		$lies = array_values(array_filter($tous, static fn (array $b): bool => (int) $b['article_id'] !== (int) $article['article_id'] && (int) $b['category_id'] === (int) $article['category_id']));

		if (count($lies) < 3)
		{
			foreach ($tous as $b)
			{
				if ((int) $b['article_id'] !== (int) $article['article_id'] && !in_array($b, $lies, TRUE))
				{
					$lies[] = $b;
				}
			}
		}

		return [
			// La liste est triée du plus récent au plus ancien : le « précédent » est plus ancien.
			'precedent' => $rang !== FALSE ? ($tous[$rang + 1] ?? NULL) : NULL,
			'suivant'   => $rang !== FALSE && $rang > 0 ? $tous[$rang - 1] : NULL,
			'lies'      => array_slice($lies, 0, 3),
		];
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
