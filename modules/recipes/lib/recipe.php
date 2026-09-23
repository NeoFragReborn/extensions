<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Recipes\Lib;

/**
 * Les décisions du module Recettes qui méritent d'être éprouvées.
 *
 * Classe PURE, sans dépendance au service locator — à l'image de `modules/forum/lib`. Les
 * ingrédients et les étapes y sont saisis en texte, une par ligne : c'est ce découpage, et les
 * bornes des durées, qui valent une épreuve ; le reste du module est de l'affichage échappé.
 */
class Recipe
{
	/** Bornes des champs numériques. Au-delà, ce n'est plus une recette mais une faute de frappe. */
	public const PARTS_MAX   = 999;
	public const MINUTES_MAX = 6000;   // cent heures : un cassoulet, à la rigueur

	/** Ce qu'on montre d'une recette dans sa fiche d'administration. */
	public const APERCU = 180;

	/**
	 * Un texte saisi une ligne par élément, découpé en liste.
	 *
	 * Les lignes vides sautent : on en laisse toujours en saisissant, et une puce vide dans la liste
	 * publiée n'a aucun sens. Les espaces de bord aussi, pour la même raison.
	 *
	 * @param  mixed $texte
	 * @return list<string>
	 */
	public static function lignes($texte): array
	{
		if (!is_string($texte))
		{
			return [];
		}

		$lignes = [];

		foreach (preg_split('/\R/u', $texte) ?: [] as $ligne)
		{
			// `\R` couvre les trois fins de ligne : un texte collé depuis Windows ou depuis un vieux
			// Mac se découpe comme les autres.
			if (($ligne = trim($ligne)) !== '')
			{
				$lignes[] = $ligne;
			}
		}

		return $lignes;
	}

	/**
	 * Un entier positif borné, ou 0 pour « non renseigné ».
	 *
	 * 0 n'est pas une valeur à afficher : une recette sans temps de cuisson ne doit pas annoncer
	 * « 0 min ». C'est la page publique qui le décide, mais c'est ici que la convention est posée.
	 *
	 * @param mixed $valeur
	 */
	public static function entier($valeur, int $max): int
	{
		if (!is_numeric($valeur))
		{
			return 0;
		}

		return max(0, min($max, (int) $valeur));
	}

	/** Une durée en minutes, écrite comme on la dit : « 1 h 30 », « 45 min », rien du tout si zéro. */
	public static function duree(int $minutes): string
	{
		if ($minutes <= 0)
		{
			return '';
		}

		if ($minutes < 60)
		{
			return $minutes.' min';
		}

		$heures = intdiv($minutes, 60);
		$reste  = $minutes % 60;

		return $reste === 0 ? $heures.' h' : $heures.' h '.str_pad((string) $reste, 2, '0', STR_PAD_LEFT);
	}

	/**
	 * La durée au format ISO 8601 attendu par `schema.org/Recipe`.
	 *
	 * C'est ce que lisent les moteurs de recherche pour présenter une recette dans leurs résultats.
	 * Une chaîne vide quand la durée n'est pas renseignée : mieux vaut ne rien déclarer qu'annoncer
	 * `PT0M`, qui serait faux.
	 */
	public static function duree_iso(int $minutes): string
	{
		if ($minutes <= 0)
		{
			return '';
		}

		$heures = intdiv($minutes, 60);
		$reste  = $minutes % 60;

		return 'PT'.($heures > 0 ? $heures.'H' : '').($reste > 0 ? $reste.'M' : '');
	}

	/** Un aperçu d'un texte, coupé à la fin d'un mot plutôt qu'au milieu. */
	public static function apercu($texte, int $max = self::APERCU): string
	{
		$texte = trim((string) preg_replace('/\s+/u', ' ', (string) $texte));

		if (mb_strlen($texte) <= $max)
		{
			return $texte;
		}

		$coupe  = mb_substr($texte, 0, $max);
		$espace = mb_strrpos($coupe, ' ');

		return rtrim($espace !== FALSE && $espace > $max / 2 ? mb_substr($coupe, 0, $espace) : $coupe, " \t\n\r,;:").'…';
	}
}
