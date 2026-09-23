<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Glossary\Lib;

/**
 * La lettre d'un terme, et son ancre.
 *
 * Classe PURE, sans dépendance au service locator. C'est la seule vraie difficulté du dictionnaire :
 * ranger « Éclaireur » sous E et non sous une lettre à part, « ÆTHER » sous A, « 1v1 » sous # — et
 * le faire sans dépendre de `intl`, que l'hébergement n'a pas forcément.
 */
class Term
{
	/** Les termes qui ne commencent pas par une lettre sont rangés ensemble, sous ce signe. */
	public const AUTRES = '#';

	/**
	 * Les lettres de l'index, dans l'ordre où on les propose.
	 *
	 * @return list<string>
	 */
	public static function alphabet(): array
	{
		return array_merge(range('A', 'Z'), [self::AUTRES]);
	}

	/**
	 * La lettre sous laquelle ranger un terme.
	 *
	 * Les accents sont retirés à la main plutôt que par `Normalizer` ou `iconv//TRANSLIT` : la
	 * première demande l'extension `intl`, la seconde rend des résultats qui changent d'une
	 * bibliothèque C à l'autre — `É` y devient parfois `'E`, ce qui donnerait une lettre « ' ».
	 *
	 * @param mixed $terme
	 */
	public static function initiale($terme): string
	{
		$terme = trim((string) $terme);

		if ($terme === '')
		{
			return self::AUTRES;
		}

		$brute = mb_substr($terme, 0, 1);

		// La table est consultée AVANT la mise en majuscule, puis après. C'est le eszett allemand qui
		// l'impose : `mb_strtoupper('ß')` rend « SS », DEUX caractères, qui ne sont plus une lettre
		// unique — le terme partait alors sous « # ». C'est l'épreuve unitaire qui l'a attrapé.
		$premiere = self::EQUIVALENTS[$brute]
			?? self::EQUIVALENTS[mb_strtoupper($brute, 'UTF-8')]
			?? mb_strtoupper($brute, 'UTF-8');

		$premiere = mb_substr($premiere, 0, 1);

		return preg_match('/^[A-Z]$/', $premiere) ? $premiere : self::AUTRES;
	}

	/**
	 * Les lettres accentuées et les ligatures, ramenées à leur lettre de rangement.
	 *
	 * Elle est écrite en majuscules, à une exception près : le eszett allemand « ß », dont la
	 * majuscule officielle est « SS », donc deux caractères. `initiale()` consulte donc la table
	 * avec le caractère BRUT avant de la consulter avec sa majuscule.
	 */
	private const EQUIVALENTS = [
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
		'Ç' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
		'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ñ' => 'N',
		'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Œ' => 'O',
		'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
		'Ý' => 'Y', 'Ÿ' => 'Y',
		'Š' => 'S', 'Ž' => 'Z', 'Ð' => 'D', 'Þ' => 'T', 'ß' => 'S',
	];

	/**
	 * Les synonymes d'un terme, saisis séparés par des virgules.
	 *
	 * @param  mixed $texte
	 * @return list<string>
	 */
	public static function synonymes($texte): array
	{
		if (!is_string($texte))
		{
			return [];
		}

		$synonymes = [];

		foreach (explode(',', $texte) as $synonyme)
		{
			if (($synonyme = trim((string) preg_replace('/\s+/u', ' ', $synonyme))) !== '')
			{
				$synonymes[] = $synonyme;
			}
		}

		return array_values(array_unique($synonymes));
	}

	/** L'ancre d'une lettre dans la page, pour l'index en tête. */
	public static function ancre(string $lettre): string
	{
		return 'lettre-'.($lettre === self::AUTRES ? 'autres' : strtolower($lettre));
	}
}
