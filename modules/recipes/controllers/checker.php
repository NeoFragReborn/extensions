<?php
declare(strict_types=1);
namespace NF\Modules\Recipes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('id', 'title')
								->from('nf_recipes_categories')
								->order_by('sort_order ASC, id ASC')
								->get();

		$par_categorie = [];

		foreach ($cats as $c)
		{
			$recettes = NeoFrag()->db	->select('id', 'title', 'intro', 'servings', 'prep_minutes', 'cook_minutes')
										->from('nf_recipes')
										->where('category_id', $c['id'])
										->where('published', '1')
										->order_by('sort_order ASC, id ASC')
										->get();

			$par_categorie[$c['id']] = ['cat' => $c, 'recipes' => $recettes];
		}

		return [$par_categorie];
	}

	/** Une recette, ou NULL — auquel cas le produit rend un 404, ce qui est la bonne réponse. */
	public function _recette($id, $title)
	{
		$recette = NeoFrag()->db	->select('r.*', 'c.title AS cat_title')
									->from('nf_recipes r')
									->join('nf_recipes_categories c', 'r.category_id = c.id')
									->where('r.id', $id)
									->where('r.published', '1')
									->row();

		return $recette ? [$recette] : NULL;
	}
}
