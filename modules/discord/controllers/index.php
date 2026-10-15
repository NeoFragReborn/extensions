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
	 *
	 * LA PREUVE VIENT DE DISCORD, PAS DU LIEN (s07, 2026-10-14). Le jeton disait quel compte Discord
	 * relier ; il suffisait donc de donner SON lien à quelqu'un pour que SON compte Discord se relie
	 * au compte du site de l'autre. Et comme la liaison s'écrit dans `nf_user_auth` — la table des
	 * connexions —, celui qui avait envoyé le lien pouvait ensuite se connecter au compte de sa
	 * victime par « Se connecter avec Discord ». Un avertissement en gras ne suffit pas contre ça.
	 *
	 * Le membre passe donc par Discord lui-même (`user/auth/discord`), qui rend l'identifiant du
	 * compte connecté. La liaison n'a lieu que si cet identifiant est EXACTEMENT celui du jeton.
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
		$prouve   = $this->_prouve_par_discord((string) $liaison['discord_id']);

		return $this->panel()->heading($this->lang('Relier mon compte Discord'), 'fab fa-discord')->body($this->view('lier', [
			'liaison'   => $liaison,
			'connecte'  => TRUE,
			'membre'    => (string) $this->user->username,
			'messages'  => $messages,
			'prouve'    => $prouve,
			// « Mes comptes liés », et non un aller-retour direct vers Discord : y relier un service
			// demande d'abord de confirmer son identité (audit du 2026-10-09 — sinon une session volée
			// y liait le Discord du voleur). Ce chemin-là est déjà éprouvé ; on y envoie, et le membre
			// revient ensuite sur le lien du bot.
			'preuve'    => url('user/auth'),
			// Deux liens protégés plutôt qu'un formulaire : sans message à reprendre, il n'aurait aucun
			// champ, et le site ne saurait pas qu'il a été envoyé.
			'relier'    => $prouve ? $this->csrf_url('discord/lier/'.$jeton.'/confirmer/0') : '',
			'reprendre' => $prouve && $messages ? $this->csrf_url('discord/lier/'.$jeton.'/confirmer/1') : '',
		]));
	}

	/** La confirmation : relier, et reprendre ou non les messages publiés depuis Discord. */
	public function _confirmer($jeton, $liaison, $reprendre)
	{
		$this->check_csrf('discord/lier/'.$jeton);

		// La vue n'est pas une garde : on revérifie ici, où la liaison s'écrit.
		if (!$this->_prouve_par_discord((string) $liaison['discord_id']))
		{
			notify($this->lang('Confirmez d’abord avec Discord que ce compte est le vôtre.'), 'danger');
			redirect('discord/lier/'.$jeton);
		}

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

	/**
	 * Le membre connecté s'est-il identifié auprès de DISCORD comme étant bien ce compte-là ?
	 *
	 * La preuve est la ligne que le passage par `user/auth/discord` écrit ou confirme dans
	 * `nf_user_auth` : son `key` est l'identifiant rendu par Discord, que personne ne peut choisir.
	 * Elle vaut donc aussi quand le membre avait déjà relié son Discord par « Mes comptes liés ».
	 */
	private function _prouve_par_discord(string $discord_id): bool
	{
		$membre = $this->_modele()->membre_lie($discord_id);

		return $membre !== NULL && $membre['user_id'] === (int) $this->user->id;
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
