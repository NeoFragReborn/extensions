<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Surveys — sondages publics avec choix unique ou multiple, résultats configurables.
 */

namespace NF\Modules\Surveys;

use NF\NeoFrag\Addons\Module;

class Surveys extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Sondages'),
			'description' => $this->lang('Sondages publics à choix unique ou multiple avec résultats configurables.'),
			'icon'        => 'fas fa-poll',
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
				''                                  => 'index',
				'{id}/{url_title}'                  => '_show',
				'vote/{id}/{url_title}'             => '_vote',
				'admin{pages}'                      => 'index',
				'admin/add'                         => '_add',
				'admin/{id}/{url_title}'            => '_edit',
				'admin/delete/{id}/{url_title}'     => '_delete',
				'admin/close/{id}/{url_title}'      => '_close'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Sondages'),
						'icon'   => 'fas fa-poll',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les sondages'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Hash de l'IP (pour anti-double-vote sans stocker l'IP en clair, RGPD-friendly).
	 */
	public static function ip_hash()
	{
		// Une adresse IPv6 compte pour son réseau /64, comme une adresse IPv4 derrière une box : sinon chaque adresse
		// tirée dans ce réseau votait une fois de plus (audit du 2026-10-09).
		$ip = \NF\NeoFrag\Libraries\Rate_Limit::bloc_ip();
		return hash('sha256', 'survey-salt:'.$ip);
	}

	/**
	 * Les résultats d'un sondage sont-ils visibles ? UNE règle pour sa page et pour le widget.
	 *
	 * Elle suit les libellés du réglage « Afficher les résultats » de l'administration. Avant elle,
	 * la page montrait les résultats à quiconque avait voté ou dès la fermeture, quel que soit le
	 * réglage — « Quand le sondage est fermé » et « Jamais (pour admin uniquement) » ne valaient
	 * donc que pour qui n'avait pas encore voté —, et le widget les montrait toujours (2026-10-04).
	 *
	 * @param string $reglage       always | after_vote | closed | never (une valeur inconnue vaut le défaut, after_vote)
	 * @param bool   $a_vote        le visiteur a déjà voté
	 * @param bool   $ferme         le sondage est fermé
	 * @param bool   $gestionnaire  le visiteur gère les sondages : il voit toujours les résultats
	 */
	public static function resultats_visibles(string $reglage, bool $a_vote, bool $ferme, bool $gestionnaire = FALSE): bool
	{
		if ($gestionnaire)
		{
			return TRUE;
		}

		switch ($reglage)
		{
			case 'always':
				return TRUE;

			case 'closed':
				return $ferme;

			case 'never':
				return FALSE;

			default:
				return $a_vote || $ferme;
		}
	}

	/** Le visiteur a-t-il voté ? Par son compte s'il est connecté, sinon par l'empreinte de son adresse IP. */
	public static function a_vote(int $survey_id): bool
	{
		[$colonne, $valeur] = self::votant();

		return !NeoFrag()->db->from('nf_surveys_votes')->where('survey_id', $survey_id)->where($colonne, $valeur)->empty();
	}

	/**
	 * Les sondages où le visiteur a voté, en une requête — pour la liste des sondages.
	 *
	 * @return array<int, true>  survey_id => TRUE
	 */
	public static function sondages_votes(): array
	{
		[$colonne, $valeur] = self::votant();

		$votes = [];

		// Une requête à UNE colonne rend des scalaires (`Db::get()`).
		foreach ((array) NeoFrag()->db->select('DISTINCT survey_id')->from('nf_surveys_votes')->where($colonne, $valeur)->get() as $survey_id)
		{
			$votes[(int) $survey_id] = TRUE;
		}

		return $votes;
	}

	/**
	 * Qui vote : [colonne, valeur] — le compte du membre connecté, sinon l'empreinte de l'IP. Calculé
	 * AVANT toute requête : le constructeur de requêtes est partagé.
	 *
	 * @return array{0: string, 1: int|string}
	 */
	private static function votant(): array
	{
		$user = NeoFrag()->user();

		return $user ? ['user_id', (int) $user->id] : ['ip_hash', self::ip_hash()];
	}
}
