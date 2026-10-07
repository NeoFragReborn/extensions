<?php
declare(strict_types=1);
namespace NF\Modules\Recipes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Recipes\Lib\Recipe;

/**
 * Les pages publiques : la liste par catégorie, et la fiche d'une recette.
 *
 * Tout ce qui s'affiche est du texte saisi en administration, échappé sans exception. Les
 * ingrédients et les étapes sont des lignes, rendues en listes — jamais du HTML : c'est ce qui
 * permet de ne rien avoir à assainir.
 */
class Index extends Controller_Module
{
	public function index($groupes)
	{
		$this	->title($this->lang('Recettes'))
				->icon('fas fa-utensils')
				->breadcrumb();

		$corps = '';
		$vide  = TRUE;

		foreach ($groupes as $groupe)
		{
			if (empty($groupe['recipes']))
			{
				continue;
			}

			$vide   = FALSE;
			$corps .= '<h2 class="h4 mt-4 mb-3">'.nf_texte($groupe['cat']['title']).'</h2>';
			$corps .= '<div class="nf-card-grid">';

			foreach ($groupe['recipes'] as $recette)
			{
				$corps .= $this->_carte($recette);
			}

			$corps .= '</div>';
		}

		if ($vide)
		{
			$corps = '<div class="alert alert-info text-center">'.$this->lang('Aucune recette pour le moment.').'</div>';
		}

		return $this->panel()->title($this->lang('Recettes'), 'fas fa-utensils')->body($corps);
	}

	/** La fiche complète d'une recette. */
	public function _recette($recette)
	{
		// Une recette n'a pas de langue à elle : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this	->title($recette['title'])
				->icon('fas fa-utensils')
				->breadcrumb($this->lang('Recettes'), url('recipes'))
				->breadcrumb();

		$total = (int) $recette['prep_minutes'] + (int) $recette['cook_minutes'];

		$corps  = '<div itemscope itemtype="https://schema.org/Recipe">';
		$corps .= '<meta itemprop="name" content="'.nf_texte($recette['title']).'" />';

		if (trim((string) $recette['intro']) !== '')
		{
			$corps .= '<p class="lead" itemprop="description">'.nf_texte($recette['intro']).'</p>';
		}

		$corps .= $this->_entete($recette, $total);

		$ingredients = Recipe::lignes($recette['ingredients']);
		$etapes      = Recipe::lignes($recette['steps']);

		if ($ingredients)
		{
			$corps .= '<h3 class="h5 mt-4">'.$this->lang('Ingrédients').'</h3><ul class="nf-recipe-ingredients">';

			foreach ($ingredients as $ingredient)
			{
				$corps .= '<li itemprop="recipeIngredient">'.nf_texte($ingredient).'</li>';
			}

			$corps .= '</ul>';
		}

		if ($etapes)
		{
			$corps .= '<h3 class="h5 mt-4">'.$this->lang('Préparation').'</h3><ol class="nf-recipe-steps">';

			foreach ($etapes as $etape)
			{
				$corps .= '<li itemprop="recipeInstructions">'.nf_texte($etape).'</li>';
			}

			$corps .= '</ol>';
		}

		$corps .= '</div>';

		return $this->panel()->title($recette['title'], 'fas fa-utensils')->body($corps);
	}

	/** Parts et durées — affichées seulement si elles sont renseignées. */
	protected function _entete($recette, int $total): string
	{
		$morceaux = [];

		if (($parts = (int) $recette['servings']) > 0)
		{
			$morceaux[] = '<span itemprop="recipeYield"><i class="fas fa-user-friends"></i> '.$this->lang('%d part|%d parts', $parts, $parts).'</span>';
		}

		foreach ([
			['prep_minutes', 'prepTime', 'far fa-clock', $this->lang('Préparation')],
			['cook_minutes', 'cookTime', 'fas fa-fire',  $this->lang('Cuisson')],
		] as [$champ, $propriete, $icone, $libelle])
		{
			if (($minutes = (int) $recette[$champ]) > 0)
			{
				$morceaux[] = '<span><i class="'.$icone.'"></i> '.nf_texte($libelle).' '.nf_texte(Recipe::duree($minutes))
					.'<meta itemprop="'.$propriete.'" content="'.Recipe::duree_iso($minutes).'" /></span>';
			}
		}

		if ($total > 0)
		{
			$morceaux[] = '<span><i class="fas fa-hourglass-half"></i> '.$this->lang('Total').' '.nf_texte(Recipe::duree($total))
				.'<meta itemprop="totalTime" content="'.Recipe::duree_iso($total).'" /></span>';
		}

		return $morceaux ? '<div class="nf-recipe-meta">'.implode(' ', $morceaux).'</div>' : '';
	}

	protected function _carte($recette): string
	{
		$slug  = url_title($recette['title']);
		$total = (int) $recette['prep_minutes'] + (int) $recette['cook_minutes'];

		$html  = '<div class="nf-content-card">';
		$html .= '<div class="nf-content-card-head"><div class="nf-content-card-title">';
		$html .= '<a href="'.url('recipes/'.(int) $recette['id'].'/'.$slug).'">'.nf_texte($recette['title']).'</a>';
		$html .= '</div></div>';

		if (trim((string) $recette['intro']) !== '')
		{
			$html .= '<div class="nf-content-card-desc">'.nf_texte(Recipe::apercu($recette['intro'])).'</div>';
		}

		$meta = [];

		if (($parts = (int) $recette['servings']) > 0)
		{
			$meta[] = '<span><i class="fas fa-user-friends"></i> '.$this->lang('%d part|%d parts', $parts, $parts).'</span>';
		}

		if ($total > 0)
		{
			$meta[] = '<span><i class="far fa-clock"></i> '.nf_texte(Recipe::duree($total)).'</span>';
		}

		if ($meta)
		{
			$html .= '<div class="nf-content-card-meta">'.implode(' ', $meta).'</div>';
		}

		return $html.'</div>';
	}
}
