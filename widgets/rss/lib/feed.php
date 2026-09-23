<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Rss\Lib;

use DateTimeImmutable;
use DateTimeZone;
use SimpleXMLElement;

/**
 * Lecture d'un flux distant : RSS 2.0, RSS 1.0 (RDF) et Atom, ramenés à une seule forme.
 *
 * Classe PURE — elle ne sort jamais sur le réseau et ne connaît ni la base ni le service locator.
 * C'est ce qui permet de l'éprouver sur des flux réels enregistrés une fois pour toutes, plutôt que
 * de croiser les doigts en production. Le contrôleur lui donne des octets, elle rend des articles.
 *
 * Deux précautions qui ne se voient pas dans le résultat
 * ------------------------------------------------------
 * 1. **Entités externes** : un flux est du XML fourni par un tiers. Une déclaration `<!ENTITY … SYSTEM
 *    "file:///etc/passwd">` transforme un lecteur naïf en lecteur de fichiers du serveur. On refuse
 *    donc tout document portant un `<!DOCTYPE`, et on analyse sans `LIBXML_NOENT` (qui, malgré son
 *    nom, SUBSTITUE les entités au lieu de les retirer) et avec `LIBXML_NONET`.
 * 2. **HTML dans les résumés** : les descriptions contiennent du balisage arbitraire, images et
 *    scripts compris. Rien n'en ressort : le résumé est réduit à du texte, et seul le texte est rendu.
 */
class Feed
{
	/** Au-delà, ce n'est plus un aperçu mais une page. */
	public const RESUME_MAX = 240;

	/**
	 * Les articles d'un flux, du plus récent au plus ancien, ou une liste vide.
	 *
	 * @return list<array{title: string, link: string, date: ?int, summary: string, author: string}>
	 */
	public static function parse(string $xml, int $combien = 5): array
	{
		$xml = trim($xml);

		if ($xml === '' || $combien < 1)
		{
			return [];
		}

		// Un DOCTYPE dans un flux n'a aucun usage légitime et ouvre la porte aux entités externes.
		if (stripos(substr($xml, 0, 512), '<!DOCTYPE') !== FALSE)
		{
			return [];
		}

		$precedent = libxml_use_internal_errors(TRUE);
		$racine    = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
		libxml_clear_errors();
		libxml_use_internal_errors($precedent);

		if (!$racine instanceof SimpleXMLElement)
		{
			return [];
		}

		$entrees = self::entrees($racine);
		$articles = [];

		foreach ($entrees as $entree)
		{
			$article = self::article($entree);

			if ($article['title'] !== '' || $article['link'] !== '')
			{
				$articles[] = $article;
			}

			if (count($articles) >= $combien)
			{
				break;
			}
		}

		return $articles;
	}

	/** Le titre du flux lui-même, pour coiffer le widget quand l'administrateur n'en a pas donné. */
	public static function titre(string $xml): string
	{
		$xml = trim($xml);

		if ($xml === '' || stripos(substr($xml, 0, 512), '<!DOCTYPE') !== FALSE)
		{
			return '';
		}

		$precedent = libxml_use_internal_errors(TRUE);
		$racine    = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
		libxml_clear_errors();
		libxml_use_internal_errors($precedent);

		if (!$racine instanceof SimpleXMLElement)
		{
			return '';
		}

		// RSS 2.0 : <rss><channel><title> ; Atom et RDF : le titre est sur la racine ou le canal.
		foreach ([$racine->channel->title ?? NULL, $racine->title ?? NULL] as $candidat)
		{
			if ($candidat !== NULL && ($titre = self::texte((string) $candidat)) !== '')
			{
				return $titre;
			}
		}

		return '';
	}

	/**
	 * Les éléments d'article, quel que soit le dialecte.
	 *
	 * @return list<SimpleXMLElement>
	 */
	private static function entrees(SimpleXMLElement $racine): array
	{
		$listes = [
			$racine->channel->item ?? NULL,   // RSS 2.0
			$racine->item ?? NULL,            // RSS 1.0 / RDF : les <item> sont frères du <channel>
			$racine->entry ?? NULL,           // Atom
		];

		foreach ($listes as $liste)
		{
			if ($liste !== NULL && count($liste) > 0)
			{
				return iterator_to_array($liste, FALSE);
			}
		}

		return [];
	}

	/** @return array{title: string, link: string, date: ?int, summary: string, author: string} */
	private static function article(SimpleXMLElement $entree): array
	{
		return [
			'title'   => self::texte((string) ($entree->title ?? '')),
			'link'    => self::lien($entree),
			'date'    => self::date($entree),
			'summary' => self::resume($entree),
			'author'  => self::auteur($entree),
		];
	}

	/**
	 * Le lien de l'article.
	 *
	 * En Atom, `<link>` n'a pas de contenu : l'adresse est dans son attribut `href`, et il peut y en
	 * avoir plusieurs — on prend celui dont le `rel` est `alternate` ou absent, jamais un `enclosure`
	 * (qui désigne un fichier joint, pas l'article).
	 */
	private static function lien(SimpleXMLElement $entree): string
	{
		if (isset($entree->link))
		{
			foreach ($entree->link as $lien)
			{
				$rel = (string) ($lien['rel'] ?? '');

				if ($rel !== '' && $rel !== 'alternate')
				{
					continue;
				}

				if (($href = trim((string) ($lien['href'] ?? ''))) !== '')
				{
					return self::lien_sur($href);
				}

				if (($texte = trim((string) $lien)) !== '')
				{
					return self::lien_sur($texte);
				}
			}
		}

		// RSS 1.0 emploie parfois <guid isPermaLink="true"> comme seule adresse.
		if (isset($entree->guid) && strtolower((string) ($entree->guid['isPermaLink'] ?? 'true')) === 'true')
		{
			return self::lien_sur(trim((string) $entree->guid));
		}

		return '';
	}

	/**
	 * Une adresse qu'on accepte d'écrire dans un `href`.
	 *
	 * Seuls `http` et `https` passent : un flux peut proposer `javascript:` ou `data:`, et le lien
	 * d'un article atterrit directement dans la page d'un site qui n'y est pour rien.
	 */
	private static function lien_sur(string $url): string
	{
		return preg_match('#^https?://#i', $url) ? $url : '';
	}

	/** L'horodatage de publication, ou NULL si le flux n'en donne pas d'exploitable. */
	private static function date(SimpleXMLElement $entree): ?int
	{
		$candidats = [
			(string) ($entree->pubDate ?? ''),      // RSS 2.0
			(string) ($entree->published ?? ''),    // Atom
			(string) ($entree->updated ?? ''),      // Atom, à défaut de published
		];

		// Dublin Core (<dc:date>), employé par RSS 1.0 et par beaucoup de RSS 2.0.
		foreach ($entree->children('http://purl.org/dc/elements/1.1/') as $nom => $valeur)
		{
			if ($nom === 'date')
			{
				$candidats[] = (string) $valeur;
			}
		}

		foreach ($candidats as $brut)
		{
			if (($brut = trim($brut)) === '')
			{
				continue;
			}

			try
			{
				return (new DateTimeImmutable($brut, new DateTimeZone('UTC')))->getTimestamp();
			}
			catch (\Exception $e)
			{
				// Une date illisible n'est pas une raison de perdre l'article : on essaie la suivante.
				continue;
			}
		}

		return NULL;
	}

	/** Le résumé, ramené à du texte et coupé à la fin d'un mot. */
	private static function resume(SimpleXMLElement $entree): string
	{
		$brut = '';

		foreach ([$entree->description ?? NULL, $entree->summary ?? NULL, $entree->content ?? NULL] as $candidat)
		{
			if ($candidat !== NULL && ($brut = trim((string) $candidat)) !== '')
			{
				break;
			}
		}

		if ($brut === '')
		{
			return '';
		}

		$texte = self::texte($brut);

		if (mb_strlen($texte) <= self::RESUME_MAX)
		{
			return $texte;
		}

		$coupe = mb_substr($texte, 0, self::RESUME_MAX);
		$espace = mb_strrpos($coupe, ' ');

		return rtrim($espace !== FALSE && $espace > self::RESUME_MAX / 2 ? mb_substr($coupe, 0, $espace) : $coupe, " \t\n\r,;:").'…';
	}

	private static function auteur(SimpleXMLElement $entree): string
	{
		// Atom : <author><name>. RSS : <author> (une adresse e-mail) ou <dc:creator>.
		if (isset($entree->author->name))
		{
			return self::texte((string) $entree->author->name);
		}

		foreach ($entree->children('http://purl.org/dc/elements/1.1/') as $nom => $valeur)
		{
			if ($nom === 'creator')
			{
				return self::texte((string) $valeur);
			}
		}

		// Une adresse e-mail nue dans le flux n'est pas affichée : la republier, c'est la donner aux
		// moissonneurs. La forme « adresse (Nom) » de RSS 2.0 permet de ne garder que le nom.
		$auteur = self::texte((string) ($entree->author ?? ''));

		if (preg_match('/\(([^)]+)\)\s*$/', $auteur, $m))
		{
			return trim($m[1]);
		}

		return filter_var($auteur, FILTER_VALIDATE_EMAIL) ? '' : $auteur;
	}

	/** Du balisage quelconque vers du texte lisible : plus aucune balise, plus aucune entité. */
	private static function texte(string $brut): string
	{
		/*
		 * DEUX passages de `strip_tags`, et le décodage ENTRE les deux.
		 *
		 * Beaucoup de flux échappent leur balisage : la description contient `&lt;time&gt;…`. Retirer
		 * les balises D'ABORD n'y touche pas, puis le décodage les fait rapparaître — et le résumé
		 * affichait `<time datetime="…">` en toutes lettres. Vu le 2026-09-22 sur notre propre flux.
		 */
		$texte = strip_tags(html_entity_decode(strip_tags($brut), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		$texte = preg_replace('/\s+/u', ' ', $texte);

		return trim((string) $texte);
	}
}
