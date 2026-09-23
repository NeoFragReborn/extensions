<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Sandbox\Lib;

/**
 * Ce que l'assainissement a retiré d'un contenu.
 *
 * Classe PURE, sans dépendance au service locator.
 *
 * Pourquoi cette comparaison existe : le bac à sable ne sert à rien s'il se contente de montrer le
 * rendu. Ce qu'un membre a besoin de comprendre, c'est POURQUOI son tableau a disparu, ou pourquoi
 * son lien ne s'affiche pas — c'est-à-dire ce que `sanitize_html()` a retiré et qu'aucun message
 * n'explique nulle part ailleurs dans le produit.
 *
 * On compare donc les noms de balises et les attributs présents avant et après. C'est grossier —
 * on ne dit pas QUELLE occurrence est tombée — mais c'est exact sur la question qui compte : « ce
 * que j'ai écrit est-il passé, oui ou non ».
 */
class Diff
{
	/** Au-delà, la liste cesse d'informer et devient un mur. */
	public const MAX_ELEMENTS = 20;

	/**
	 * Les noms de balises d'un fragment HTML, chacun une fois.
	 *
	 * Lecture par expression régulière et non par analyseur : on veut les balises telles qu'elles
	 * sont ÉCRITES, y compris celles qu'un analyseur corrigerait ou jetterait en silence — ce sont
	 * justement celles-là qui intéressent.
	 *
	 * @param  mixed $html
	 * @return list<string>
	 */
	public static function balises($html): array
	{
		if (!is_string($html))
		{
			return [];
		}

		preg_match_all('/<\s*([a-z][a-z0-9]*)\b/i', $html, $m);

		$balises = array_map('strtolower', $m[1]);

		sort($balises);

		return array_values(array_unique($balises));
	}

	/**
	 * Les noms d'attributs d'un fragment HTML, chacun une fois.
	 *
	 * @param  mixed $html
	 * @return list<string>
	 */
	public static function attributs($html): array
	{
		if (!is_string($html))
		{
			return [];
		}

		$attributs = [];

		// On ne regarde QUE l'intérieur des balises : sans cela, « style : voir ci-dessous » dans le
		// texte compterait pour un attribut `style`.
		if (preg_match_all('/<\s*[a-z][a-z0-9]*\b([^>]*)>/i', $html, $balises))
		{
			foreach ($balises[1] as $interieur)
			{
				if (preg_match_all('/([a-z_:][a-z0-9_:.-]*)\s*=/i', $interieur, $m))
				{
					foreach ($m[1] as $nom)
					{
						$attributs[] = strtolower($nom);
					}
				}
			}
		}

		sort($attributs);

		return array_values(array_unique($attributs));
	}

	/**
	 * Ce qui était là avant et qui n'y est plus.
	 *
	 * @return array{tags: list<string>, attributes: list<string>, truncated: bool}
	 */
	public static function retire($avant, $apres): array
	{
		$balises   = array_values(array_diff(self::balises($avant), self::balises($apres)));
		$attributs = array_values(array_diff(self::attributs($avant), self::attributs($apres)));

		$coupe = count($balises) > self::MAX_ELEMENTS || count($attributs) > self::MAX_ELEMENTS;

		return [
			'tags'       => array_slice($balises, 0, self::MAX_ELEMENTS),
			'attributes' => array_slice($attributs, 0, self::MAX_ELEMENTS),
			'truncated'  => $coupe,
		];
	}

	/**
	 * Le contenu du bac à sable, ramené à ce qu'on accepte de stocker.
	 *
	 * La borne n'est pas de la prudence excessive : un brouillon par membre, sans limite, c'est une
	 * table qui grossit au rythme du plus bavard. Cent mille caractères, c'est déjà vingt fois un
	 * long message de forum.
	 *
	 * @param mixed $contenu
	 */
	public static function contenu($contenu, int $max = 100000): string
	{
		if (!is_string($contenu))
		{
			return '';
		}

		return mb_strlen($contenu) > $max ? mb_substr($contenu, 0, $max) : $contenu;
	}
}
