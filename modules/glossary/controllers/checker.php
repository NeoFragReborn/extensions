<?php
declare(strict_types=1);
namespace NF\Modules\Glossary\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Glossary\Lib\Term;

class Checker extends Module_Checker
{
	public function index()
	{
		$termes = NeoFrag()->db	->select('t.id', 't.term', 't.initial', 't.definition', 't.synonyms', 'c.title AS cat_title')
								->from('nf_glossary_terms t')
								->join('nf_glossary_categories c', 't.category_id = c.id')
								->where('t.published', '1')
								->order_by('t.initial ASC, t.term ASC')
								->get();

		// Regroupés par lettre, dans l'ordre de l'index. Une lettre sans terme n'entre pas dans le
		// tableau : la page ne montre que les lettres qui mènent quelque part.
		$par_lettre = [];

		foreach (Term::alphabet() as $lettre)
		{
			$par_lettre[$lettre] = [];
		}

		foreach ($termes as $terme)
		{
			$lettre = isset($par_lettre[$terme['initial']]) ? $terme['initial'] : Term::AUTRES;
			$par_lettre[$lettre][] = $terme;
		}

		return [array_filter($par_lettre), count($termes)];
	}
}
