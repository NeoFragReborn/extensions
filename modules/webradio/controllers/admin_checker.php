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

		$db = NeoFrag()->db	->select('id', 'title', 'host', 'description', 'day', 'start_time', 'end_time', 'published')
							->from('nf_webradio_shows');

		if ($filtres['q'] !== '')
		{
			$comme = '%'.$filtres['q'].'%';
			$db->where('title LIKE', $comme, 'OR', 'host LIKE', $comme, 'OR', 'description LIKE', $comme);
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
		return $s ? [$s] : NULL;
	}

	public function _s_delete($id, $title)
	{
		$s = NeoFrag()->db->select('id', 'title')->from('nf_webradio_shows')->where('id', $id)->row();
		return $s ? [$s] : NULL;
	}
}
