<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Bug Tracker — tickets internes (bugs, features, questions).
 */

namespace NF\Modules\Bugtracker;

use NF\NeoFrag\Addons\Module;

class Bugtracker extends Module
{
	/** Les types de ticket, ceux de la colonne `nf_bug_tickets.type`. */
	public const TYPES = ['bug', 'feature', 'question', 'other'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Bugtracker'),
			'description' => $this->lang('Suivi de bugs et tickets internes avec types, priorités et statuts configurables.'),
			'icon'        => 'fas fa-bug',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                              => 'index',
				'new'                           => '_new',
				'{id}/{url_title}'              => '_show',
				'{id}/{url_title}/comment'      => '_comment',
				'admin{pages}'                  => 'index',
				'admin/{id}/{url_title}'        => '_edit',
				'admin/delete/{id}/{url_title}' => '_delete'
			]
		];
	}

	/** Les événements du Bugtracker que le fil de l'API retient (le bot Discord tient ainsi le fil de chaque ticket). */
	public const EVENEMENTS = ['bugtracker.ticket.created', 'bugtracker.ticket.updated', 'bugtracker.ticket.deleted', 'bugtracker.comment.created', 'bugtracker.comment.edited', 'bugtracker.comment.deleted'];

	/**
	 * Le module api n'est pas chargé pendant une page du Bugtracker : il ne peut pas écouter lui-même,
	 * le Bugtracker lui confie ses événements (comme le forum).
	 *
	 * couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
	 */
	public function __init()
	{
		foreach (self::EVENEMENTS as $evenement)
		{
			$this->events->on($evenement, static function ($charge) use ($evenement) {
				if (is_array($charge) && ($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
				{
					$api->consigner($evenement, $charge);
				}
			});
		}
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Tickets'),
						'icon'   => 'fas fa-bug',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les tickets'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	public static function status_label($status)
	{
		$L = NeoFrag()->module('bugtracker');
		return [
			'open'        => '<span class="badge text-bg-info"><span class="dot"></span> '.$L->lang('Ouvert').'</span>',
			'in_progress' => '<span class="badge text-bg-warning"><span class="dot"></span> '.$L->lang('En cours').'</span>',
			'resolved'    => '<span class="badge text-bg-success"><span class="dot"></span> '.$L->lang('Résolu').'</span>',
			'closed'      => '<span class="badge text-bg-secondary"><span class="dot"></span> '.$L->lang('Fermé').'</span>',
			'wont_fix'    => '<span class="badge text-bg-light"><span class="dot"></span> '.$L->lang('Wont fix').'</span>',
			'duplicate'   => '<span class="badge text-bg-dark"><span class="dot"></span> '.$L->lang('Doublon').'</span>'
		][$status] ?? $status;
	}

	public static function priority_label($priority)
	{
		$L = NeoFrag()->module('bugtracker');
		return [
			'low'      => '<span class="badge text-bg-light"><i class="fas fa-arrow-down"></i> '.$L->lang('Faible').'</span>',
			'normal'   => '<span class="badge text-bg-secondary"><span class="dot"></span> '.$L->lang('Normale').'</span>',
			'high'     => '<span class="badge text-bg-warning"><i class="fas fa-arrow-up"></i> '.$L->lang('Haute').'</span>',
			'critical' => '<span class="badge text-bg-danger"><i class="fas fa-exclamation-triangle"></i> '.$L->lang('Critique').'</span>'
		][$priority] ?? $priority;
	}

	public static function type_label($type)
	{
		$L = NeoFrag()->module('bugtracker');
		return [
			'bug'      => '<span class="badge text-bg-danger"><i class="fas fa-bug"></i> '.$L->lang('Bug').'</span>',
			'feature'  => '<span class="badge text-bg-info"><i class="fas fa-star"></i> '.$L->lang('Feature').'</span>',
			'question' => '<span class="badge text-bg-warning"><i class="far fa-question-circle"></i> '.$L->lang('Question').'</span>',
			'other'    => '<span class="badge text-bg-secondary"><i class="fas fa-tag"></i> '.$L->lang('Autre').'</span>'
		][$type] ?? $type;
	}
}
