<?php
declare(strict_types=1);
namespace NF\Modules\Glossary\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Glossary\Lib\Term;

/**
 * La page publique du lexique : un index alphabétique, puis les termes lettre par lettre.
 *
 * Tout est échappé : terme, définition, synonymes, nom de catégorie. Une définition est du texte,
 * pas du HTML — les retours à la ligne sont rendus, rien d'autre.
 */
class Index extends Controller_Module
{
	public function index($par_lettre, $total)
	{
		// La page est faite des définitions, qui n'ont pas de langue à elles : sa canonique est dans la
		// langue première du site.
		nf_seo_sans_langue();

		$this	->title($this->lang('Dictionnaire'))
				->icon('fas fa-book')
				->breadcrumb();

		if (!$par_lettre)
		{
			return $this->panel()
						->title($this->lang('Dictionnaire'), 'fas fa-book')
						->body('<div class="alert alert-info text-center">'.$this->lang('Aucun terme pour le moment.').'</div>');
		}

		$this->css('glossary');

		$corps  = '<nav class="nf-glossary-index" aria-label="'.nf_texte($this->lang('Index alphabétique')).'">';

		foreach (Term::alphabet() as $lettre)
		{
			// Une lettre sans terme n'est pas un lien : elle reste affichée, en grisé, pour que
			// l'index garde sa forme d'un dictionnaire à l'autre.
			$corps .= isset($par_lettre[$lettre])
				? '<a href="#'.Term::ancre($lettre).'">'.nf_texte($lettre).'</a>'
				: '<span class="nf-glossary-index-off">'.nf_texte($lettre).'</span>';
		}

		$corps .= '</nav>';
		$corps .= '<p class="text-muted">'.$this->lang('%d terme|%d termes', (int) $total, (int) $total).'</p>';

		foreach ($par_lettre as $lettre => $termes)
		{
			$corps .= '<h2 class="h4 mt-4" id="'.Term::ancre((string) $lettre).'">'.nf_texte($lettre).'</h2>';
			$corps .= '<dl class="nf-glossary">';

			foreach ($termes as $terme)
			{
				$corps .= '<dt>'.nf_texte($terme['term']);

				if (($synonymes = Term::synonymes($terme['synonyms'])))
				{
					$corps .= ' <span class="nf-glossary-synonyms">('.nf_texte(implode(', ', $synonymes)).')</span>';
				}

				$corps .= '</dt>';
				$corps .= '<dd>'.nl2br(nf_texte($terme['definition']));
				$corps .= ' <span class="nf-glossary-cat"><i class="fas fa-folder"></i> '.nf_texte($terme['cat_title']).'</span>';
				$corps .= '</dd>';
			}

			$corps .= '</dl>';
		}

		return $this->panel()->title($this->lang('Dictionnaire'), 'fas fa-book')->body($corps);
	}
}
