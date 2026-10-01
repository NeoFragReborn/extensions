<?php
declare(strict_types=1);
namespace NF\Widgets\Articles\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	/*
	 * Les widgets du Blog (2026-10-01). Tous partent de la liste des billets VISIBLES dans
	 * la langue affichée, celle du module : un widget ne peut pas montrer un brouillon, un billet
	 * programmé, à la corbeille ou d'une autre langue — le précédent montrait un billet programmé
	 * avant sa date.
	 */

	public function index($config = [])
	{
		$config  = (array) $config + ['count' => 5, 'display_panel' => 'oui'];
		$billets = array_slice($this->_billets(), 0, max(1, min(20, (int) $config['count'])));

		return $this->_rendre($config, $this->lang('Derniers billets'), 'far fa-newspaper', $billets ? $this->view('index', ['billets' => $billets]) : '');
	}

	public function populaires($config = [])
	{
		$config  = (array) $config + ['count' => 5, 'display_panel' => 'oui'];
		$billets = $this->_billets();

		usort($billets, static fn (array $a, array $b): int => (int) $b['views'] <=> (int) $a['views']);

		return $this->_rendre($config, $this->lang('Les plus lus'), 'fas fa-fire', $billets ? $this->view('populaires', ['billets' => array_slice($billets, 0, max(1, min(20, (int) $config['count'])))]) : '');
	}

	public function une($config = [])
	{
		$config  = (array) $config + ['display_panel' => 'oui'];
		$billets = $this->_billets();
		$une     = NULL;

		// Le même choix que la liste du Blog : le plus récent « à la une », sinon le plus récent.
		foreach ($billets as $billet)
		{
			if (!empty($billet['featured']))
			{
				$une = $billet;
				break;
			}
		}

		$une ??= $billets[0] ?? NULL;

		return $this->_rendre($config, $this->lang('À la une'), 'fas fa-star', $une ? $this->view('une', ['billet' => $une]) : '');
	}

	public function categories($config = [])
	{
		$config     = (array) $config + ['display_panel' => 'oui'];
		$categories = [];

		foreach ($this->_billets() as $billet)
		{
			$id = (int) $billet['category_id'];
			$categories[$id] ??= ['adresse' => 'articles/category/'.$id.'/'.url_title((string) $billet['category_name']), 'titre' => (string) $billet['category_title'], 'total' => 0];
			$categories[$id]['total']++;
		}

		usort($categories, static fn (array $a, array $b): int => strcmp($a['titre'], $b['titre']));

		return $this->_rendre($config, $this->lang('Catégories'), 'far fa-folder-open', $categories ? $this->view('compteurs', ['lignes' => $categories]) : '', $this->lang('Aucune catégorie pour le moment.'));
	}

	public function tags($config = [])
	{
		$config = (array) $config + ['display_panel' => 'oui'];
		$tags   = [];

		foreach ($this->_billets() as $billet)
		{
			foreach (array_filter(array_map('trim', explode(',', (string) $billet['tags']))) as $tag)
			{
				$tags[$tag] = ($tags[$tag] ?? 0) + 1;
			}
		}

		arsort($tags);

		return $this->_rendre($config, $this->lang('Tags'), 'fas fa-tags', $tags ? $this->view('tags', ['tags' => array_slice($tags, 0, 20, TRUE)]) : '', $this->lang('Aucun tag pour le moment.'));
	}

	public function archives($config = [])
	{
		$config = (array) $config + ['display_panel' => 'oui'];
		$mois   = [];

		foreach ($this->_billets() as $billet)
		{
			$cle = substr((string) $billet['date'], 0, 7);
			$mois[$cle] ??= ['adresse' => 'articles/archives/'.str_replace('-', '/', $cle), 'titre' => timetostr('F Y', $cle.'-01'), 'total' => 0];
			$mois[$cle]['total']++;
		}

		krsort($mois);

		return $this->_rendre($config, $this->lang('Archives'), 'far fa-calendar-alt', $mois ? $this->view('compteurs', ['lignes' => array_slice($mois, 0, 12)]) : '');
	}

	/** Le widget, dans un panneau ou nu selon son réglage ; sans billet, il le dit. */
	private function _rendre(array $config, $titre, string $icone, $corps, $vide = NULL)
	{
		$this->css('articles');

		// Le titre est une traduction et le corps une vue : des objets qui se lisent comme du texte.
		$titre = (string) $titre;
		$corps = (string) $corps;

		$corps = '<div class="widget-blog">'.($corps !== '' ? $corps : '<p class="widget-blog-vide">'.($vide ?? $this->lang('Aucun billet pour le moment.')).'</p>').'</div>';

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $corps;
		}

		return $this->panel()
					->heading($titre, $icone)
					->body($corps)
					->footer('<a href="'.url('articles').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Tout le Blog').'</a>', 'right');
	}

	/**
	 * Les billets visibles du Blog, du plus récent au plus ancien.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function _billets(): array
	{
		$modele = ($module = $this->module('articles')) ? $module->model('articles') : NULL;

		return $modele instanceof \NF\Modules\Articles\Models\Articles ? array_values((array) $modele->get_articles()) : [];
	}
}
