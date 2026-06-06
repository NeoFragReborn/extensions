<?php
/**
 * https://neofr.ag
 * Module Articles — articles longs avec sommaire auto, temps de lecture, catégories, tags.
 * Différent de "News" (publications brèves d'équipe).
 */

namespace NF\Modules\Articles;

use NF\NeoFrag\Addons\Module;

class Articles extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Articles'),
			'description' => $this->lang('Articles longs avec catégories, tags, sommaire automatique et temps de lecture.'),
			'icon'        => 'far fa-newspaper',
			'link'        => 'https://neofr.ag',
			'author'      => 'user_id',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{page}'                                   => 'index',
				'{id}/{url_title}'                         => '_article',
				'tag/{url_title}{pages}'                   => '_tag',
				'category/{id}/{url_title}{pages}'         => '_category',

				//Admin
				'admin{pages}'                             => 'index',
				'admin/add'                                => '_add',
				'admin/history/{id}/{url_title}'           => '_history',
				'admin/revision/restore/{id}/{url_title}/{id}' => '_revision_restore',
				'admin/delete/{id}/{url_title}'            => '_delete',
				'admin/categories/add'                     => '_categories_add',
				'admin/categories/{id}/{url_title}'        => '_categories_edit',
				'admin/categories/delete/{id}/{url_title}' => '_categories_delete',
				'admin/{id}/{url_title}'                   => '_edit'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_number('articles_per_page')
										->title($this->lang('Articles par page'))
										->value($this->config->articles_per_page ?: 10)
							)
							->success(function($data){
								$this->config('articles_per_page', $data['articles_per_page']);
								notify($this->lang('Configuration modifiée'));
								refresh();
							})
							->submit($this->lang('Enregistrer'));
			}
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Articles',
						'icon'   => 'far fa-newspaper',
						'access' => [
							'add_articles'      => ['title' => $this->lang('Ajouter'),  'icon' => 'fas fa-plus',          'admin' => TRUE],
							'modify_articles'   => ['title' => $this->lang('Modifier'), 'icon' => 'fas fa-edit',          'admin' => TRUE],
							'delete_articles'   => ['title' => $this->lang('Supprimer'),'icon' => 'far fa-trash-alt',     'admin' => TRUE],
							'add_categories'    => ['title' => $this->lang('Catégories — Ajouter'),  'icon' => 'fas fa-plus',      'admin' => TRUE],
							'modify_categories' => ['title' => $this->lang('Catégories — Modifier'), 'icon' => 'fas fa-edit',      'admin' => TRUE],
							'delete_categories' => ['title' => $this->lang('Catégories — Supprimer'),'icon' => 'far fa-trash-alt', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Calcule le temps de lecture estimé (200 mots/minute, standard).
	 */
	public static function read_time_minutes($html)
	{
		$words = preg_split('/\s+/', strip_tags($html), -1, PREG_SPLIT_NO_EMPTY);
		$minutes = max(1, (int) ceil(count($words) / 200));
		return $minutes;
	}

	/**
	 * Génère un sommaire HTML depuis les <h2> et <h3> du content.
	 * Ajoute un id="..." à chaque heading pour ancres.
	 *
	 * @param string $html (modifié par référence : ajoute les id="")
	 * @return string HTML du sommaire (vide si moins de 2 headings)
	 */
	public static function build_toc(&$html)
	{
		$counter = 0;
		$entries = [];

		$html = preg_replace_callback('#<(h[23])\b([^>]*)>(.*?)</\1>#is', function($m) use (&$counter, &$entries){
			$counter++;
			$level = $m[1];
			$existing_attrs = $m[2];
			$inner = $m[3];
			$slug = 'toc-'.$counter.'-'.preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim(strip_tags($inner))));
			$slug = trim($slug, '-');

			if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/', $existing_attrs, $matched_id))
			{
				$slug = $matched_id[1];
			}
			else
			{
				$existing_attrs .= ' id="'.htmlspecialchars($slug).'"';
			}

			$entries[] = ['level' => $level, 'slug' => $slug, 'title' => trim(strip_tags($inner))];
			return '<'.$level.$existing_attrs.'>'.$inner.'</'.$level.'>';
		}, $html);

		if (count($entries) < 2)
		{
			return '';
		}

		$out = '<div class="article-toc panel card mb-3"><div class="card-header"><i class="fas fa-list-ul"></i> '.NeoFrag()->lang('Sommaire').'</div><ul class="list-group list-group-flush">';
		foreach ($entries as $e)
		{
			$indent = $e['level'] === 'h3' ? ' style="padding-left:2rem"' : '';
			$out .= '<li class="list-group-item border-0 py-1"'.$indent.'><a href="#'.htmlspecialchars($e['slug']).'">'.htmlspecialchars($e['title']).'</a></li>';
		}
		$out .= '</ul></div>';
		return $out;
	}
}
