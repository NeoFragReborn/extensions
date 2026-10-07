<?php
declare(strict_types=1);
namespace NF\Modules\Quotes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

/**
 * La page publique du recueil.
 *
 * Tout ce qui s'affiche ici est du TEXTE saisi en administration, échappé sans exception — y
 * compris l'adresse de la source, qui part dans un `href` et que le modèle a déjà ramenée à
 * `http`/`https`. Une citation n'a pas de mise en forme : elle n'a donc pas besoin d'éditeur riche,
 * et n'en offre pas.
 */
class Index extends Controller_Module
{
	public function index($groupes)
	{
		// La page est faite des citations, qui n'ont pas de langue à elles : sa canonique est dans la
		// langue première du site.
		nf_seo_sans_langue();

		$this	->title($this->lang('Citations'))
				->icon('fas fa-quote-right')
				->breadcrumb();

		$corps = '';
		$vide  = TRUE;

		foreach ($groupes as $groupe)
		{
			if (empty($groupe['quotes']))
			{
				continue;
			}

			$vide   = FALSE;
			$corps .= '<h2 class="h4 mt-4 mb-3">'.nf_texte($groupe['cat']['title']).'</h2>';
			$corps .= '<div class="nf-quotes-list">';

			foreach ($groupe['quotes'] as $citation)
			{
				$corps .= $this->_citation($citation);
			}

			$corps .= '</div>';
		}

		if ($vide)
		{
			$corps = '<div class="alert alert-info text-center">'.$this->lang('Aucune citation pour le moment.').'</div>';
		}

		return $this->panel()->title($this->lang('Citations'), 'fas fa-quote-right')->body($corps);
	}

	/** Une citation, en `<blockquote>` avec son attribution en `<cite>` — le balisage que le sens appelle. */
	protected function _citation($citation)
	{
		$html = '<figure class="nf-quote">';
		$html .= '<blockquote class="blockquote"><p>'.nl2br(nf_texte($citation['quote'])).'</p></blockquote>';

		$auteur = trim((string) $citation['author']);
		$source = trim((string) $citation['source']);
		$url    = trim((string) $citation['source_url']);

		if ($auteur !== '' || $source !== '')
		{
			$html .= '<figcaption class="blockquote-footer">';

			if ($auteur !== '')
			{
				$html .= nf_texte($auteur);
			}

			if ($source !== '')
			{
				$cite = '<cite title="'.nf_texte($source).'">'.nf_texte($source).'</cite>';

				// L'adresse a déjà été ramenée à http/https à l'enregistrement ; on la revalide tout
				// de même, car une ligne peut venir d'un import ou d'une base modifiée à la main.
				if ($url !== '' && preg_match('#^https?://#i', $url))
				{
					$cite = '<a href="'.nf_texte($url).'" target="_blank" rel="noopener">'.$cite.'</a>';
				}

				$html .= ($auteur !== '' ? ', ' : '').$cite;
			}

			$html .= '</figcaption>';
		}

		return $html.'</figure>';
	}
}
