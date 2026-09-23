<?php
declare(strict_types=1);
namespace NF\Modules\Quotes\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('id', 'title')
								->from('nf_quotes_categories')
								->order_by('sort_order ASC, id ASC')
								->get();

		$par_categorie = [];

		foreach ($cats as $c)
		{
			$citations = NeoFrag()->db	->select('id', 'quote', 'author', 'source', 'source_url')
										->from('nf_quotes')
										->where('category_id', $c['id'])
										->where('published', '1')
										->order_by('sort_order ASC, id ASC')
										->get();

			$par_categorie[$c['id']] = ['cat' => $c, 'quotes' => $citations];
		}

		return [$par_categorie];
	}
}
