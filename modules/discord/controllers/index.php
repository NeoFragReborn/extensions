<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * couplage(forum): les messages publiés depuis Discord ne sont comptés et rattachés qu'après la garde
 * `module('forum')` ; sans le forum, il n'y a rien à rattacher.
 */

namespace NF\Modules\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	/**
	 * Relier son compte Discord depuis Discord (`/forum account link`) : le bot donne au
	 * membre un lien à usage unique, valable quinze minutes. Il l'ouvre connecté — ou se connecte, ou
	 * crée son compte, puis revient —, voit les deux comptes, et confirme. Ce qu'il avait publié
	 * depuis Discord sous son pseudo peut, s'il le veut, revenir à son compte.
	 */
	public function _lier($jeton, $liaison)
	{
		$this->title($this->lang('Relier mon compte Discord'))->icon('fab fa-discord');

		if (!$liaison)
		{
			return $this->panel()->heading($this->lang('Relier mon compte Discord'), 'fab fa-discord')->body(
				'<div class="alert alert-warning mb-0">'.icon('fas fa-hourglass-end').' '.$this->lang('Ce lien a expiré ou a déjà servi. Sur Discord, relancez la commande %s pour en recevoir un nouveau.', '<code>/forum account link</code>').'</div>'
			);
		}

		if (!$this->user())
		{
			return $this->panel()->heading($this->lang('Relier mon compte Discord'), 'fab fa-discord')->body($this->view('lier', [
				'liaison'   => $liaison,
				'connecte'  => FALSE,
			]));
		}

		$messages = $this->_messages_publies($liaison['discord_id']);

		return $this->panel()->heading($this->lang('Relier mon compte Discord'), 'fab fa-discord')->body($this->view('lier', [
			'liaison'   => $liaison,
			'connecte'  => TRUE,
			'membre'    => (string) $this->user->username,
			'messages'  => $messages,
			// Deux liens protégés plutôt qu'un formulaire : sans message à reprendre, il n'aurait aucun
			// champ, et le site ne saurait pas qu'il a été envoyé.
			'relier'    => $this->csrf_url('discord/lier/'.$jeton.'/confirmer/0'),
			'reprendre' => $messages ? $this->csrf_url('discord/lier/'.$jeton.'/confirmer/1') : '',
		]));
	}

	/** La confirmation : relier, et reprendre ou non les messages publiés depuis Discord. */
	public function _confirmer($jeton, $liaison, $reprendre)
	{
		$this->check_csrf('discord/lier/'.$jeton);

		$modele   = $this->_modele();
		$resultat = $modele->lier_compte((int) $this->user->id, $liaison['discord_id'], $liaison['username']);

		if (!$resultat['ok'])
		{
			notify($resultat['erreur'] === 'discord_taken'
				? $this->lang('Ce compte Discord est déjà lié à un autre membre.')
				: ($resultat['erreur'] === 'member_has_discord'
					? $this->lang('Votre compte est déjà lié à un autre compte Discord : déliez-le d’abord, dans « Mes comptes liés ».')
					: $this->lang('La connexion par Discord n’est pas installée sur ce site.')), 'danger');
			redirect('discord/lier/'.$jeton);
		}

		$modele->consommer_liaison($jeton);

		$repris = $reprendre ? $this->_rattacher($liaison['discord_id']) : 0;

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('user.auth.linked', ['details' => 'discord '.$liaison['discord_id'].($repris ? ', messages: '.$repris : '')]);

		notify($repris
			? $this->lang('Votre compte Discord est relié, et vos %d message(s) sont maintenant à votre nom.', $repris)
			: $this->lang('Votre compte Discord est relié : vos messages sur Discord paraîtront sous votre compte du site.'));
		redirect('user/auth');
	}

	/** Combien de messages du forum cette personne a publiés depuis Discord sous son pseudo. */
	private function _messages_publies(string $discord_id): int
	{
		$forum  = $this->module('forum');
		$modele = $forum ? $forum->model('forum') : NULL;

		return $modele instanceof \NF\Modules\Forum\Models\Forum ? $modele->messages_identite('discord', $discord_id) : 0;
	}

	private function _rattacher(string $discord_id): int
	{
		$forum  = $this->module('forum');
		$modele = $forum ? $forum->model('forum') : NULL;

		return $modele instanceof \NF\Modules\Forum\Models\Forum ? $modele->rattacher_identite('discord', $discord_id, (int) $this->user->id) : 0;
	}

	/** Le modèle du module, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Discord\Models\Discord
	{
		$modele = $this->model('discord');

		if (!$modele instanceof \NF\Modules\Discord\Models\Discord)
		{
			throw new \LogicException('modèle du module Discord introuvable');
		}

		return $modele;
	}
}
