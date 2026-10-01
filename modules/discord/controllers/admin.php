<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * couplage(forum): les forums du site ne sont lus, dans `_salons()`, qu'après la garde
 * `module('forum')` ; sans le forum, il n'y a simplement aucun forum à relier.
 */

namespace NF\Modules\Discord\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Discord\Discord;

class Admin extends Controller_Module
{
	/*
	 * L'administration du bot Discord. Le bot donne un signe de vie toutes les trente
	 * secondes environ ; sans nouvelles depuis plus de quatre-vingt-dix secondes, il est tenu pour
	 * hors ligne.
	 */

	private const HORS_LIGNE_APRES = 90;

	public function index()
	{
		$this->title($this->lang('Discord'))->icon('fab fa-discord');

		$modele   = $this->_modele();
		$reglages = $modele->reglages();
		$vie      = (array) json_decode((string) $modele->etat('heartbeat'), TRUE);
		$age      = isset($vie['at']) ? time() - (int) $vie['at'] : NULL;

		// Vivant mais interrupteur coupé : en pause. En marche mais pas (encore) connecté à Discord :
		// il s'y connecte — ou n'y arrive pas, et son journal dit pourquoi.
		if ($age === NULL || $age > self::HORS_LIGNE_APRES)
		{
			$statut = 'hors_ligne';
		}
		else if (!$reglages['running'])
		{
			$statut = 'pause';
		}
		else
		{
			$statut = !empty($vie['connected']) ? 'en_ligne' : 'connexion';
		}

		$donnees = [
			'reglages' => $reglages,
			'vie'      => $vie,
			'age'      => $age,
			'statut'   => $statut,
			'journal'  => $this->_journal_traduit($modele->journal(30)),
			'cle_bot'  => (bool) $this->db->select('COUNT(*)')->from('nf_api_tokens')->where('name', 'Bot Discord')->where('revoked_at', NULL)->row(),
			'liens'    => [
				'marche'      => $this->csrf_url('admin/discord/commande/marche'),
				'pause'       => $this->csrf_url('admin/discord/commande/pause'),
				'redemarrer'  => $this->csrf_url('admin/discord/commande/redemarrer'),
			],
			'salons'   => count($modele->salons()),
			'roles'    => count($modele->roles()),
			// Le lien qui ajoute le bot au serveur, avec ses seules permissions (cf. Discord::PERMISSIONS_DISCORD).
			'invitation' => $reglages['client_id'] !== ''
				? 'https://discord.com/oauth2/authorize?client_id='.rawurlencode($reglages['client_id']).'&scope=bot%20applications.commands&permissions='.Discord::PERMISSIONS_DISCORD
				: '',
		];

		return '<div class="row g-3 mb-3">'
				.'<div class="col-12 col-xl-6">'.$this->admin_card('fas fa-robot', $this->lang('État du bot'), $this->view('admin/etat', $donnees)).'</div>'
				.'<div class="col-12 col-xl-6">'.$this->admin_card('fab fa-discord', $this->lang('Réglages'), $this->view('admin/reglages', $donnees)).'</div>'
			.'</div>'
			.$this->admin_card('fas fa-list', $this->lang('Journal du bot'), $donnees['journal']
				? $this->view('admin/journal', $donnees)
				: $this->admin_empty('fas fa-list', $this->lang('Le bot n’a encore rien écrit dans son journal.')), '', '', (bool) $donnees['journal']);
	}

	/**
	 * Une liste de correspondances dans sa carte, ou l'invitation à en créer une. Les textes arrivent
	 * de `lang()`, qui rend un objet traduisible : d'où les paramètres non typés.
	 */
	private function _correspondances($titre, array $lignes, array $colonnes, $vide): string
	{
		return $this->admin_card('fas fa-exchange-alt', (string) $titre, $lignes
			? $this->view('admin/correspondances', ['lignes' => $lignes, 'titres' => $colonnes])
			: $this->admin_empty('fas fa-unlink', (string) $vide), '', '', (bool) $lignes);
	}

	/** La connexion : l'application Discord, le serveur, la clé du bot (gardée chiffrée). */
	public function _connexion()
	{
		$this->title($this->lang('Connexion à Discord'))->icon('fab fa-discord')->breadcrumb();

		$reglages = $this->_modele()->reglages();

		$this->form()
			 ->add_rules([
				'client_id' => ['label' => $this->lang('Identifiant de l’application'), 'type' => 'text', 'value' => $reglages['client_id'],
				                'description' => $this->lang('« Application ID », sur le portail des développeurs Discord.')],
				'guild_id'  => ['label' => $this->lang('Identifiant du serveur'), 'type' => 'text', 'value' => $reglages['guild_id'],
				                'description' => $this->lang('Clic droit sur le serveur dans Discord → « Copier l’identifiant du serveur » (mode développeur).')],
				'token'     => ['label' => $this->lang('Clé du bot (token)'), 'type' => 'password',
				                'description' => $reglages['token_set'] ? $this->lang('Une clé est enregistrée, chiffrée. Laissez vide pour la garder.') : $this->lang('Onglet « Bot » du portail des développeurs → « Reset Token ».')],
				'nicknames' => ['label' => $this->lang('Pseudos'), 'type' => 'checkbox', 'value' => ['1'],
				                'values' => ['1' => $this->lang('Donner aux membres liés leur pseudo du site sur le serveur')], 'checked' => ['1' => $reglages['nicknames']]],
			 ])
			 ->add_submit($this->lang('Enregistrer'), 'fas fa-check')
			 ->add_back('admin/discord');

		if ($this->form()->is_valid($post))
		{
			$client = trim((string) ($post['client_id'] ?? ''));
			$guilde = trim((string) ($post['guild_id'] ?? ''));

			if (($client !== '' && !ctype_digit($client)) || ($guilde !== '' && !ctype_digit($guilde)))
			{
				notify($this->lang('Les identifiants Discord ne contiennent que des chiffres.'), 'danger');
			}
			else
			{
				$this->_modele()->enregistrer_connexion($client, $guilde, trim((string) ($post['token'] ?? '')), in_array('1', (array) ($post['nicknames'] ?? []), TRUE));

				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.connexion', ['details' => trim((string) ($post['token'] ?? '')) !== '' ? 'token' : 'settings']);

				notify($this->lang('Connexion enregistrée. Le bot la relit dans la minute.'));
				redirect('admin/discord');
			}
		}

		return $this->admin_back('admin/discord').$this->admin_card('fab fa-discord', $this->lang('Connexion à Discord'), $this->form()->display());
	}

	/**
	 * La clé d'API du bot : créée avec les droits qu'il lui faut, montrée une fois, avec le fichier à
	 * poser sur la machine du bot.
	 */
	public function _cle()
	{
		$this->title($this->lang('Clé d’accès du bot'))->icon('fas fa-key')->breadcrumb();

		$api        = $this->module('api');
		$modele_api = $api ? $api->model('api') : NULL;

		if (!$modele_api instanceof \NF\Modules\Api\Models\Api)
		{
			notify($this->lang('Le module API doit être installé.'), 'danger');
			redirect('admin/discord');

			return '';
		}

		// Une seule clé « Bot Discord » active : la précédente est révoquée.
		foreach ((array) $this->db->select('token_id')->from('nf_api_tokens')->where('name', 'Bot Discord')->where('revoked_at', NULL)->get() as $ancienne)
		{
			$modele_api->revoquer((int) $ancienne);
		}

		$cle = $modele_api->creer('Bot Discord', Discord::DROITS_DU_BOT, $this->user() ? (int) $this->user->id : NULL);

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('api.token.created', ['details' => 'Bot Discord — '.implode(', ', Discord::DROITS_DU_BOT)]);

		$fichier = "NF_SITE_URL=".site_origin().$this->url->base."\nNF_API_KEY=".$cle."\n";

		return $this->admin_back('admin/discord').$this->admin_card('fas fa-key', $this->lang('Clé d’accès du bot'),
			'<div class="alert alert-warning">'.icon('fas fa-exclamation-triangle').' '.$this->lang('Copiez ces deux lignes maintenant : la clé ne sera plus jamais affichée. L’ancienne clé du bot, s’il y en avait une, est révoquée.').'</div>'
			.'<p>'.$this->lang('Sur la machine qui fait tourner le bot, ces deux lignes vont dans son fichier de configuration (%s) :', '<code>.env</code>').'</p>'
			.'<textarea class="form-control font-monospace mb-3" rows="3" readonly="readonly" aria-label="'.$this->lang('Configuration du bot').'">'.htmlspecialchars($fichier).'</textarea>'
			.'<a class="btn btn-light" href="'.url('admin/discord').'">'.$this->lang('Retour').'</a>');
	}

	/** Marche, pause, redémarrage : le bot obéit à son prochain signe de vie. */
	public function _commande($commande)
	{
		$this->check_csrf('admin/discord');

		$modele = $this->_modele();

		if ($commande === 'marche' || $commande === 'pause')
		{
			$modele->mettre_en_marche($commande === 'marche');
			notify($commande === 'marche' ? $this->lang('Le bot se met en marche dans la minute.') : $this->lang('Le bot se met en pause dans la minute.'));
		}
		else if ($commande === 'redemarrer')
		{
			$modele->commander('restart');
			notify($this->lang('Le bot redémarre dans la minute.'));
		}

		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.commande', ['details' => (string) $commande]);

		redirect('admin/discord');
	}

	/** Les salons Discord reliés aux forums du site. */
	public function _salons()
	{
		$this->title($this->lang('Salons et forums'))->icon('fas fa-exchange-alt')->breadcrumb();

		$modele  = $this->_modele();
		$guilde  = $this->_guilde($modele);
		$salons  = [];

		foreach ((array) ($guilde['channels'] ?? []) as $c)
		{
			// Les salons Forum (type 15) seulement : un sujet du site y devient un fil, et l'inverse.
			if ((int) ($c['type'] ?? -1) === 15)
			{
				$salons[(string) $c['id']] = (string) $c['name'];
			}
		}

		// Les forums du site, titres traduits ; pas ceux qui ne sont qu'un lien vers une adresse extérieure.
		$forums       = [];
		$forum        = $this->module('forum');
		$modele_forum = $forum ? $forum->model('forum') : NULL;

		if ($modele_forum instanceof \NF\Modules\Forum\Models\Forum)
		{
			// Une ligne d'adresse peut exister vide : seul un forum dont l'adresse est remplie est un lien.
			foreach ((array) $this->db	->select('f.forum_id', $modele_forum->titre_forum('f').' AS title', 'u.url')
										->from('nf_forum f')
										->join('nf_forum_url u', 'u.forum_id = f.forum_id', 'LEFT')
										->order_by('f.order', 'f.forum_id')
										->get() as $f)
			{
				if ((string) ($f['url'] ?? '') === '')
				{
					$forums[(int) $f['forum_id']] = (string) $f['title'];
				}
			}
		}

		$this->form()
			 ->add_rules([
				'channel_id' => $salons
					? ['label' => $this->lang('Salon Discord'), 'type' => 'select', 'values' => $salons, 'rules' => 'required']
					: ['label' => $this->lang('Identifiant du salon Discord'), 'type' => 'text', 'rules' => 'required', 'description' => $this->lang('La liste des salons Forum du serveur apparaît ici dès que le bot est en ligne.')],
				'forum_id'   => ['label' => $this->lang('Forum du site'), 'type' => 'select', 'values' => $forums, 'rules' => 'required'],
				'mode'       => ['label' => $this->lang('Synchronisation'), 'type' => 'select', 'value' => 'all', 'values' => ['all' => $this->lang('Tout : chaque fil devient un sujet, et l’inverse'), 'reaction' => $this->lang('À la demande : un fil passe sur le site quand on y pose la réaction choisie')]],
				'emoji'      => ['label' => $this->lang('Réaction (mode à la demande)'), 'type' => 'text', 'value' => '📌'],
			 ])
			 ->add_submit($this->lang('Relier'), 'fas fa-link');

		if ($this->form()->is_valid($post))
		{
			$channel = trim((string) $post['channel_id']);

			if (!ctype_digit($channel) || !isset($forums[(int) $post['forum_id']]))
			{
				notify($this->lang('Salon ou forum invalide.'), 'danger');
			}
			else if (array_filter($modele->salons(), static fn (array $s): bool => $s['channel_id'] === $channel || $s['forum_id'] === (int) $post['forum_id']))
			{
				notify($this->lang('Ce salon ou ce forum est déjà relié.'), 'danger');
			}
			else
			{
				$modele->ajouter_salon($channel, (int) $post['forum_id'], (string) ($post['mode'] ?? 'all'), trim((string) ($post['emoji'] ?? '')));
				notify($this->lang('Salon relié.'));
				redirect('admin/discord/salons');
			}
		}

		$lignes = [];

		foreach ($modele->salons() as $s)
		{
			$lignes[] = [
				'gauche'    => $salons[$s['channel_id']] ?? '#'.$s['channel_id'],
				'droite'    => $forums[$s['forum_id']] ?? '?',
				'detail'    => $s['mode'] === 'reaction' ? $this->lang('À la demande, par la réaction %s', $s['emoji']) : $this->lang('Tout'),
				'supprimer' => $this->csrf_url('admin/discord/salons/supprimer/'.$s['mapping_id']),
			];
		}

		return $this->admin_back('admin/discord')
			.$this->_correspondances($this->lang('Salons reliés'), $lignes, [$this->lang('Salon Discord'), $this->lang('Forum du site'), $this->lang('Synchronisation')], $this->lang('Aucun salon relié pour le moment.'))
			.$this->admin_card('fas fa-link', $this->lang('Relier un salon'), $this->form()->display());
	}

	public function _salons_supprimer($mapping)
	{
		$this->check_csrf('admin/discord/salons');
		$this->_modele()->supprimer_salon((int) $mapping['mapping_id']);
		notify($this->lang('Salon délié.'));
		redirect('admin/discord/salons');
	}

	/** Les groupes du site reliés aux rôles du serveur Discord. */
	public function _roles()
	{
		$this->title($this->lang('Groupes et rôles'))->icon('fas fa-user-tag')->breadcrumb();

		$modele = $this->_modele();
		$guilde = $this->_guilde($modele);
		$roles  = [];

		foreach ((array) ($guilde['roles'] ?? []) as $r)
		{
			// Ni @everyone, ni les rôles tenus par une intégration : Discord refuse de les donner.
			if (empty($r['managed']) && (string) ($r['name'] ?? '') !== '@everyone')
			{
				$roles[(string) $r['id']] = (string) $r['name'];
			}
		}

		$groupes = [];

		$coeur = NeoFrag()->groups;

		foreach ($coeur instanceof \NF\NeoFrag\Core\Groups ? (array) $coeur() : [] as $cle => $g)
		{
			if ($cle !== 'visitors')
			{
				$groupes[(string) $cle] = (string) ($g['title'] ?? $cle);
			}
		}

		$this->form()
			 ->add_rules([
				'group_key' => ['label' => $this->lang('Groupe du site'), 'type' => 'select', 'values' => $groupes, 'rules' => 'required'],
				'role_id'   => $roles
					? ['label' => $this->lang('Rôle Discord'), 'type' => 'select', 'values' => $roles, 'rules' => 'required']
					: ['label' => $this->lang('Identifiant du rôle Discord'), 'type' => 'text', 'rules' => 'required', 'description' => $this->lang('La liste des rôles apparaît ici dès que le bot est en ligne.')],
			 ])
			 ->add_submit($this->lang('Relier'), 'fas fa-link');

		if ($this->form()->is_valid($post))
		{
			$role = trim((string) $post['role_id']);

			if (!ctype_digit($role) || !isset($groupes[(string) $post['group_key']]))
			{
				notify($this->lang('Groupe ou rôle invalide.'), 'danger');
			}
			else if (array_filter($modele->roles(), static fn (array $r): bool => $r['group_key'] === (string) $post['group_key']))
			{
				notify($this->lang('Ce groupe est déjà relié à un rôle.'), 'danger');
			}
			else
			{
				$modele->ajouter_role((string) $post['group_key'], $role);
				notify($this->lang('Groupe relié.'));
				redirect('admin/discord/roles');
			}
		}

		$lignes = [];

		foreach ($modele->roles() as $r)
		{
			$lignes[] = [
				'gauche'    => $groupes[$r['group_key']] ?? $r['group_key'],
				'droite'    => $roles[$r['role_id']] ?? '@'.$r['role_id'],
				'detail'    => '',
				'supprimer' => $this->csrf_url('admin/discord/roles/supprimer/'.$r['mapping_id']),
			];
		}

		return $this->admin_back('admin/discord')
			.$this->_correspondances($this->lang('Groupes reliés'), $lignes, [$this->lang('Groupe du site'), $this->lang('Rôle Discord'), ''], $this->lang('Aucun groupe relié pour le moment.'))
			.$this->admin_card('fas fa-link', $this->lang('Relier un groupe'), $this->form()->display());
	}

	public function _roles_supprimer($mapping)
	{
		$this->check_csrf('admin/discord/roles');
		$this->_modele()->supprimer_role((int) $mapping['mapping_id']);
		notify($this->lang('Groupe délié.'));
		redirect('admin/discord/roles');
	}

	/** Le serveur Discord tel que le bot l'a décrit à son dernier signe de vie (salons, rôles), ou rien. */
	private function _guilde(\NF\Modules\Discord\Models\Discord $modele): array
	{
		$vie = (array) json_decode((string) $modele->etat('heartbeat'), TRUE);

		return (array) ($vie['guild'] ?? []);
	}

	/**
	 * Le journal du bot dans la langue de l'administrateur : chaque ligne dont le module connaît le
	 * modèle est traduite ; les autres gardent la phrase française envoyée par le bot.
	 *
	 * @param list<array{level: string, message: string, template: string, args: list<string>, created_at: string}> $journal
	 * @return list<array{level: string, message: string, template: string, args: list<string>, created_at: string}>
	 */
	private function _journal_traduit(array $journal): array
	{
		$module = $this->module('discord');

		if (!$module instanceof Discord)
		{
			return $journal;
		}

		foreach ($journal as &$ligne)
		{
			$ligne['message'] = $module->traduire_journal($ligne['template'], $ligne['args']) ?? $ligne['message'];
		}

		unset($ligne);

		return $journal;
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
