<?php
declare(strict_types=1);

namespace NF\Modules\Bugtracker\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Bugtracker\Bugtracker;

class Ajax extends Controller_Module
{
	/**
	 * Les tickets encore ouverts qui ressemblent à celui qu'on est en train d'écrire (2026-10-01).
	 *
	 * Le formulaire « Nouveau ticket » l'interroge pendant la frappe du titre : « Déjà signalé ? ».
	 * Chaque mot d'au moins trois lettres du titre est cherché dans les titres ouverts ; les tickets
	 * qui en partagent le plus viennent d'abord. Les signalements en double se rattrapent ainsi avant
	 * d'être écrits, plutôt qu'après, à la main.
	 */
	public function similaires()
	{
		$mots = array_slice(array_values(array_unique(array_filter(
			preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(trim((string) ($_GET['q'] ?? '')))) ?: [],
			static fn (string $mot): bool => mb_strlen($mot) >= 3
		))), 0, 6);

		if (!$mots)
		{
			return $this->output->json(['tickets' => []]);
		}

		// Point d'entrée public et LIKE non indexé : la même limite légère que la recherche du site.
		$limite = new \NF\NeoFrag\Libraries\Rate_Limit($this);
		$cle    = 'bugtracker_similaires:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip();

		if (!$limite->check($cle)['allowed'])
		{
			return $this->output->json(['tickets' => []]);
		}

		$limite->hit($cle, 60, 60, 60);

		$conditions = [];

		// Un titre écrit par un formulaire est rangé encodé (« r&eacute;agit ») : chaque mot est cherché
		// sous ses deux formes, sans quoi un mot accentué ne trouvait jamais rien.
		foreach ($mots as $mot)
		{
			foreach (array_unique([$mot, utf8_htmlentities($mot)]) as $forme)
			{
				array_push($conditions, 't.title LIKE', '%'.addcslashes($forme, '%_').'%', 'OR');
			}
		}

		$this->db	->select('t.id', 't.title', 't.type', 't.status')
					->from('nf_bug_tickets t')
					->where('t.status', ['open', 'in_progress']);

		call_user_func_array([$this->db, 'where'], $conditions);

		$tickets = [];

		foreach ($this->db->limit(40)->get() as $ticket)
		{
			$titre = mb_strtolower(html_entity_decode((string) $ticket['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			$score = count(array_filter($mots, static fn (string $mot): bool => str_contains($titre, $mot)));

			$tickets[] = [
				'id'     => (int) $ticket['id'],
				'title'  => html_entity_decode((string) $ticket['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
				'url'    => url('bugtracker/'.$ticket['id'].'/'.url_title($ticket['title'])),
				'type'   => trim(strip_tags(Bugtracker::type_label($ticket['type']))),
				'status' => trim(strip_tags(Bugtracker::status_label($ticket['status']))),
				'score'  => $score,
			];
		}

		usort($tickets, static fn (array $a, array $b): int => [$b['score'], $b['id']] <=> [$a['score'], $a['id']]);

		return $this->output->json(['tickets' => array_map(static function (array $t): array {
			unset($t['score']);
			return $t;
		}, array_slice($tickets, 0, 5))]);
	}
}
