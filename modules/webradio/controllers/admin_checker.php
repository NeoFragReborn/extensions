<?php
declare(strict_types=1);
namespace NF\Modules\Webradio\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;
use NF\Modules\Webradio\Lib\Schedule;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$filtres = [
			'q'      => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
			'day'    => isset($_GET['day']) && in_array((int) $_GET['day'], Schedule::JOURS, TRUE) ? (int) $_GET['day'] : 0,
			'status' => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		// La LISTE montre la meilleure traduction ; l'écran d'ÉDITION ouvre la langue où l'on travaille.
		$db = NeoFrag()->db	->select('s.id', 'sl.title', 's.host', 'sl.description', 's.day', 's.start_time', 's.end_time', 's.published')
							->from('nf_webradio_shows s')
							->join_lang('nf_webradio_shows_lang sl', 'show_id', 's.id', NULL, 'LEFT');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('sl.title LIKE', $comme, 'OR', 's.host LIKE', $comme, 'OR', 'sl.description LIKE', $comme);
		}
		if ($filtres['day'])
		{
			$db->where('day', $filtres['day']);
		}
		if ($filtres['status'] === 'published')
		{
			$db->where('published', 1);
		}
		else if ($filtres['status'] === 'draft')
		{
			$db->where('published', 0);
		}

		$creneaux = $db->order_by('day ASC, start_time ASC, id ASC')->limit(self::SEARCH_CAP)->get();

		$publies = 0;
		foreach ($creneaux as $c) { if (!empty($c['published'])) $publies++; }

		$filtres['matched']   = count($creneaux);
		$filtres['published'] = $publies;
		$filtres['drafts']    = count($creneaux) - $publies;
		$filtres['active']    = $filtres['q'] !== '' || $filtres['day'] || $filtres['status'] !== '';

		$filtres['sort_cols'] = [
			'grille' => $this->lang('Grille'),
			'title'  => $this->lang('Titre'),
			'status' => $this->lang('Statut')
		];
		list($creneaux, $filtres['sort']) = $this->sort_items($creneaux, [
			// Le tri « grille » est celui de la semaine : le jour d'abord, l'heure ensuite. Trier sur
			// l'heure seule mélangerait le lundi matin et le dimanche matin.
			'grille' => static fn ($c) => sprintf('%d %s', (int) $c['day'], (string) $c['start_time']),
			'title'  => 'title',
			'status' => 'published'
		], 'grille', 'asc');

		$station = [
			'name'   => (string) (NeoFrag()->config->webradio_name ?: ''),
			'stream' => Schedule::flux(NeoFrag()->config->webradio_stream),
			'origin' => (string) (NeoFrag()->config->webradio_origin ?: ''),
		];

		return [$station, $this->module->pagination->fix_items_per_page(30)->get_data($creneaux, $page), $filtres];
	}

	public function _s_add() { return [NULL]; }

	public function _s_edit($id, $title)
	{
		$s = NeoFrag()->db->select('*')->from('nf_webradio_shows')->where('id', $id)->row();

		if ($s)
		{
			$ecrit = NeoFrag()->db	->select('title', 'description')
									->from('nf_webradio_shows_lang')
									->where('show_id', (int) $s['id'])
									->where('lang', (string) $this->config->lang->info()->name)
									->row();

			$lu = NeoFrag()->db	->select('title')
								->from('nf_webradio_shows_lang')
								->where('show_id', (int) $s['id'])
								->order_by("lang = 'fr' DESC, lang ASC")
								->row();

			// Le texte TEL QU'IL EST ÉCRIT dans la langue de l'administration (vide si rien n'y est
			// encore écrit), et le titre LU pour l'en-tête.
			$s = $s + [
				'title'       => is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '',
				'description' => is_array($ecrit) ? (string) ($ecrit['description'] ?? '') : '',
				'title_lu'    => is_string($lu) ? $lu : (is_array($ecrit) ? (string) ($ecrit['title'] ?? '') : '')
			];
		}
		return $s ? [$s] : NULL;
	}

	public function _s_delete($id, $title)
	{
		$s = NeoFrag()->db	->select('s.id', 'sl.title')
							->from('nf_webradio_shows s')
							->join_lang('nf_webradio_shows_lang sl', 'show_id', 's.id', NULL, 'LEFT')
							->where('s.id', $id)
							->row();
		return $s ? [$s] : NULL;
	}
}
