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

		$pages = $this->module->pagination;

		return [
			$pages->fix_items_per_page($this->config->articles_per_page ?: 10)->get_data($reste, $page),
			$this->_premiere_page($page) ? $une : NULL,
			$this->_barre($tous),
			(string) $pages->get_pagination(),
		];
	}

	public function _article($article_id, $title)
	{
		if (($article = $this->_modele()->get_article($article_id)))
		{
			if (count_view('article', $article_id))
			{
				$this->_modele()->increment_views($article_id);
			}

			return [$article, $this->_autour($article)];
		}
	}

	public function _category($category_id, $title, $page = '')
	{
		$articles = $this->_modele()->get_articles('category', $category_id);

		if (empty($articles))
		{
			return;
		}

		$pages = $this->module->pagination;

		return [
			$pages->fix_items_per_page($this->config->articles_per_page ?: 10)->get_data($articles, $page),
			$category_id,
			$title,
			$this->_barre($this->_modele()->get_articles()),
			(string) $pages->get_pagination(),
		];
	}

	public function _tag($tag, $page = '')
	{
		$articles = $this->_modele()->get_articles('tag', $tag);

		$pages = $this->module->pagination;

		return [
			$pages->fix_items_per_page($this->config->articles_per_page ?: 10)->get_data($articles, $page),
			$tag,
			$this->_barre($this->_modele()->get_articles()),
			(string) $pages->get_pagination(),
		];
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
