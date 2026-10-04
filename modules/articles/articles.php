<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Articles — articles longs avec sommaire auto, temps de lecture, catégories, tags.
 * Différent de "News" (publications brèves d'équipe).
 */

namespace NF\Modules\Articles;

use NF\NeoFrag\Addons\Module;

class Articles extends Module
{

	/** Descripteurs de contenu — cf. Module::content_types(). */
	public function declare_content_types()
	{
		return [
			'articles' => [
				'table' => 'nf_articles', 'pk' => 'article_id', 'author' => 'user_id',
				'reactable' => TRUE, 'subscribable' => TRUE, 'revisable' => TRUE,
				// « article » au singulier circule aussi (reactions, gamification) : meme contenu.
				'aliases' => ['article'],
			],
			'article-category' => [
				'table' => 'nf_articles_categories', 'pk' => 'category_id',
				'subscribable' => TRUE,
			],
		];
	}

	/** URL publique d'un article (titre lu dans la langue courante). */
	public function content_url($type, $id)
	{
		if ($type !== 'articles' && $type !== 'article')
		{
			return '';
		}

		$title = $this->db	->select('title')
							->from('nf_articles_lang')
							->where('article_id', (int) $id)
							->where('lang', $this->config->lang->info()->name)
							->row();

		return $title ? 'articles/'.(int) $id.'/'.url_title($title) : '';
	}

	/** Corbeille : type restaurable declare par le module lui-meme (cf. Trash::types()). */
	public function trash_types()
	{
		return [
			'article' => [
				'label'   => 'Article', 'table' => 'nf_articles',
				'pk'      => 'article_id', 'lang' => 'nf_articles_lang', 'title' => 'title',
				'restore' => 'restore_article', 'purge' => 'purge_article', 'url' => 'articles/%d/%s',
			],
		];
	}
	protected function __info()
	{
		return [
			'title'       => $this->lang('Blog'),
			'description' => $this->lang('Articles longs avec catégories, tags, sommaire automatique et temps de lecture.'),
			'icon'        => 'far fa-newspaper',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
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
				'auteur/{id}/{url_title}{pages}'           => '_auteur',
				'archives/{id}/{id}{pages}'                => '_archives',
				'serie/{id}/{url_title}'                   => '_serie',

				//Admin
				'admin{pages}'                             => 'index',
				'admin/add'                                => '_add',
				'admin/categories'                         => '_categories',
				'admin/series'                             => '_series',
				'admin/series/add'                         => '_series_add',
				'admin/series/{id}/{url_title}'            => '_series_edit',
				'admin/series/delete/{id}/{url_title}'     => '_series_delete',
				'admin/history/{id}/{url_title}'           => '_history',
				'admin/revision/restore/{id}/{url_title}/{id}' => '_revision_restore',
				'admin/delete/{id}/{url_title}'            => '_delete',
				'admin/categories/add'                     => '_categories_add',
				'admin/categories/{id}/{url_title}'        => '_categories_edit',
				'admin/categories/delete/{id}/{url_title}' => '_categories_delete',
				'admin/{id}/{url_title}'                   => '_edit'
			],
			'settings'    => function(){
				$form = $this->form2()
							->rule($this->form_number('articles_per_page')
										->title($this->lang('Articles par page'))
										->value($this->config->articles_per_page ?: 10)
							);

				// Les mises en page du Blog : la liste, la fiche d'un billet.
				foreach (['liste' => $this->lang('Mise en page de la liste'), 'fiche' => $this->lang('Mise en page d’un billet')] as $ecran => $titre)
				{
					$form->rule('articles_'.$ecran, $titre, self::mise_en_page($ecran, (string) $this->config->{'articles_'.$ecran}), 'select', self::mises_en_page($ecran));
				}

				return $form
							->success(function($data){
								$this->config('articles_per_page', $data['articles_per_page']);
								$config = $this->config;
								$config('articles_liste', self::mise_en_page('liste', (string) ($data['articles_liste'] ?? '')));
								$config('articles_fiche', self::mise_en_page('fiche', (string) ($data['articles_fiche'] ?? '')));
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
						'title'  => $this->lang('Articles'),
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
	 * Les mises en page du Blog que l'administrateur peut choisir : la liste et la fiche
	 * d'un billet. La première de chaque liste est celle par défaut — la proposition fusionnée.
	 *
	 * @return array<string, string>
	 */
	public static function mises_en_page(string $ecran): array
	{
		$m = NeoFrag()->module('articles');

		return $ecran === 'fiche'
			? ['fusion' => (string) $m->lang('Sommaire, colonne de lecture et encart'), 'sommaire' => (string) $m->lang('Sommaire à gauche'), 'centree' => (string) $m->lang('Colonne centrée')]
			: ['barre' => (string) $m->lang('Magazine avec barre latérale'), 'magazine' => (string) $m->lang('Magazine pleine largeur'), 'lignes' => (string) $m->lang('Lecture en lignes')];
	}

	/** La mise en page retenue : celle du réglage si elle existe, sinon celle par défaut. */
	public static function mise_en_page(string $ecran, string $reglage): string
	{
		$choix = $ecran === 'fiche' ? ['fusion', 'sommaire', 'centree'] : ['barre', 'magazine', 'lignes'];

		return in_array($reglage, $choix, TRUE) ? $reglage : $choix[0];
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
	 * Le corps d'un article peut valoir NULL en base : la méthode l'accepte et le normalise, et
	 * la variable de l'appelant ressort toujours en chaîne.
	 *
	 * @param string|null $html (modifié par référence : ajoute les id="")
	 * @param-out string  $html
	 * @return string HTML du sommaire (vide si moins de 2 headings)
	 */
	public static function build_toc(&$html)
	{
		// Le corps d'un article peut valoir NULL en base : sans cela, `preg_replace_callback()`
		// recevrait NULL la ou il attend une chaine.
		$html    = (string) $html;
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
				$existing_attrs .= ' id="'.htmlspecialchars((string) ($slug)).'"';
			}

			$entries[] = ['level' => $level, 'slug' => $slug, 'title' => trim(strip_tags($inner))];
			return '<'.$level.$existing_attrs.'>'.$inner.'</'.$level.'>';
		}, $html) ?? $html;

		/*
		 * Le `?? $html` ci-dessus n'est pas une precaution de style. `preg_replace_callback()` rend
		 * NULL quand le moteur abandonne — au-dela de `pcre.backtrack_limit`, ce que le `(.*?)` de
		 * ce motif peut atteindre sur un article long. Sans ce repli, le corps de l'article etait
		 * remplace par NULL : la page repondait 200 avec un article VIDE, et rien nulle part ne
		 * l'expliquait. On prefere un sommaire manquant a un article efface.
		 */

		if (count($entries) < 2)
		{
			return '';
		}

		$out = '<div class="article-toc card mb-3"><div class="card-header"><i class="fas fa-list-ul"></i> '.NeoFrag()->module('articles')->lang('Sommaire').'</div><ul class="list-group list-group-flush">';
		foreach ($entries as $e)
		{
			$indent = $e['level'] === 'h3' ? ' style="padding-left:2rem"' : '';
			$out .= '<li class="list-group-item border-0 py-1"'.$indent.'><a href="#'.htmlspecialchars((string) ($e['slug'])).'">'.htmlspecialchars((string) ($e['title'])).'</a></li>';
		}
		$out .= '</ul></div>';
		return $out;
	}
}
