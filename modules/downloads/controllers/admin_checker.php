<?php
declare(strict_types=1);
namespace NF\Modules\Downloads\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		/*
		 * Les LISTES montrent la meilleure traduction (`join_lang`), jamais le vide : un fichier
		 * écrit dans une seule langue reste visible dans les cinq autres. L'écran d'ÉDITION, lui,
		 * ouvre la langue où l'on travaille, et elle seule (m32, 2026-10-15).
		 */
		$cats = NeoFrag()->db	->select('c.id', 'cl.title', 'c.sort_order', 'COUNT(f.id) AS nb')
								->from('nf_downloads_categories c')
								->join_lang('nf_downloads_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
								->join('nf_downloads f', 'c.id = f.category_id', 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC')
								->get();

		$cat_ids = [];
		foreach ($cats as $c) { $cat_ids[(int)$c['id']] = TRUE; }

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($cat_ids[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('f.*', 'fl.title', 'fl.description', 'cl.title AS cat_title')
							->from('nf_downloads f')
							->join_lang('nf_downloads_lang fl', 'download_id', 'f.id', NULL, 'LEFT')
							->join('nf_downloads_categories c', 'f.category_id = c.id')
							->join_lang('nf_downloads_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('fl.title LIKE', $like, 'OR', 'f.version LIKE', $like);
		}
		if ($filters['category'])
		{
			$db->where('f.category_id', $filters['category']);
		}
		if ($filters['status'] === 'published')
		{
			$db->where('f.published', 1);
		}
		else if ($filters['status'] === 'draft')
		{
			$db->where('f.published', 0);
		}

		$files = $db->order_by('c.sort_order ASC, fl.title ASC')->limit(self::SEARCH_CAP)->get();

		$pub = 0;
		foreach ($files as $f) { if (!empty($f['published'])) $pub++; }

		$filters['matched']   = count($files);
		$filters['published'] = $pub;
		$filters['drafts']    = count($files) - $pub;
		$filters['active']    = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		$filters['sort_cols'] = ['date' => $this->lang('Date'), 'title' => $this->lang('Titre'), 'downloads' => $this->lang('Téléchargements')];
		list($files, $filters['sort']) = $this->sort_items($files, ['date' => 'created_at', 'title' => 'title', 'downloads' => 'downloads_count'], 'date', 'desc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($files, $page), $filters];
	}

	public function _file_add() { return [NULL]; }
	public function _file_edit($id, $title)
	{
		$f = NeoFrag()->db->select('*')->from('nf_downloads')->where('id', $id)->row();
		return $f ? [$this->avec_texte($f, 'nf_downloads_lang', 'download_id', (int) $f['id'])] : NULL;
	}
	public function _file_delete($id, $title)
	{
		$f = NeoFrag()->db	->select('d.id', 'dl.title')
							->from('nf_downloads d')
							->join_lang('nf_downloads_lang dl', 'download_id', 'd.id', NULL, 'LEFT')
							->where('d.id', $id)
							->row();
		return $f ? [$f] : NULL;
	}
	public function _cat_add() { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_downloads_categories')->where('id', $id)->row();
		return $c ? [$this->avec_texte($c, 'nf_downloads_categories_lang', 'category_id', (int) $c['id'])] : NULL;
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db	->select('c.id', 'cl.title')
							->from('nf_downloads_categories c')
							->join_lang('nf_downloads_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
							->where('c.id', $id)
							->row();
		return $c ? [$c] : NULL;
	}

	/**
	 * La ligne de base, augmentée du texte TEL QU'IL EST ÉCRIT dans la langue de l'administration
	 * (`title`, `description`, vides si rien n'y est encore écrit) et du titre LU pour les en-têtes
	 * (`title_lu`, la meilleure traduction) — sinon l'écran s'intitulerait « Éditer : ».
	 *
	 * @param array<string, mixed> $ligne
	 * @return array<string, mixed>
	 */
	private function avec_texte(array $ligne, string $table, string $cle, int $id): array
	{
		$ecrit = NeoFrag()->db	->select('title', 'description')
								->from($table)
								->where($cle, $id)
								->where('lang', (string) $this->config->lang->info()->name)
								->row();

		$lu = NeoFrag()->db	->select('title')
							->from($table)
							->where($cle, $id)
							->order_by("lang = 'fr' DESC, lang ASC")
							->row();

		return $ligne + [
			'title'       => is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '',
			'description' => is_array($ecrit) ? (string) ($ecrit['description'] ?? '') : '',
			'title_lu'    => is_string($lu) ? $lu : (is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '')
		];
	}
}
