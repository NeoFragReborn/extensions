<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Quotes\Lib;

/**
 * Les quelques décisions du module Citations qui méritent d'être éprouvées.
 *
 * Classe PURE, sans aucune dépendance au service locator — comme `modules/forum/lib`. C'est ce qui
 * permet de les couvrir par un test unitaire : tant qu'elles vivaient dans le contrôleur, il aurait
 * fallu tout le framework pour appeler trois lignes.
 */
class Quote
{
	/** Ce qu'on montre d'une citation dans sa fiche d'administration, avant de la couper. */
	public const APERCU = 180;

	/**
	 * Une adresse qu'on accepte d'écrire dans un `href`.
	 *
	 * Seuls `http` et `https` passent. `javascript:` saisi ici atterrirait dans le lien de la source
	 * sur la page publique — c'est-à-dire exactement ce qu'on ne veut pas laisser faire, même à un
	 * administrateur, parce qu'un compte d'administration se compromet.
	 *
	 * @param mixed $url
	 */
	public static function lien_sur($url): string
	{
		if (!is_string($url))
		{
			return '';
		}

		$url = trim($url);

		return $url !== '' && preg_match('#^https?://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== FALSE ? $url : '';
	}

	/** Un aperçu d'une citation, coupé à la fin d'un mot plutôt qu'au milieu. */
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
