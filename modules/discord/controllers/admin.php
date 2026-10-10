<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * couplage(forum): les forums du site ne sont lus, dans `_forums_du_site()`, qu'après la garde
 * `module('forum')` ; sans le forum, il n'y a simplement aucun forum à relier ni à mettre en place.
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

		$cle_bot = $this->db->select('scopes')->from('nf_api_tokens')->where('name', 'Bot Discord')->where('revoked_at', NULL)->order_by('token_id DESC')->row();
		$cle_bot = is_string($cle_bot) ? $cle_bot : NULL;

		$donnees = [
			'jeton'    => $this->csrf_token(),
			'reglages' => $reglages,
			'vie'      => $vie,
			'age'      => $age,
			'statut'   => $statut,
			'journal'  => $this->_journal_traduit($modele->journal(30)),
			'cle_bot'  => $cle_bot !== NULL,
			// Une clé créée par une version précédente n'a pas les droits ajoutés depuis (le Bugtracker…).
			'droits_manquants' => $cle_bot !== NULL ? array_values(array_diff(Discord::DROITS_DU_BOT, array_filter(explode(',', $cle_bot)))) : [],
			'liens'    => [
				'marche'      => $this->csrf_url('admin/discord/commande/marche'),
				'pause'       => $this->csrf_url('admin/discord/commande/pause'),
				'redemarrer'  => $this->csrf_url('admin/discord/commande/redemarrer'),
				'resync'      => $this->csrf_url('admin/discord/commande/resynchroniser'),
			],
			'fonctionnalites' => count($modele->fonctionnalites()),
			'salons'   => count($modele->salons()),
			'roles'    => count($modele->roles()),
			// Le lien qui ajoute le bot au serveur, avec ses seules permissions (cf. Discord::PERMISSIONS_DISCORD).
			'invitation' => $reglages['client_id'] !== ''
				? 'https://discord.com/oauth2/authorize?client_id='.rawurlencode($reglages['client_id']).'&scope=bot%20applications.commands&permissions='.Discord::PERMISSIONS_DISCORD
				: '',
		];

		return $this->_onglets('index')
			.'<div class="row g-3 mb-3">'
				.'<div class="col-12 col-xl-6">'.$this->admin_card('fas fa-robot', $this->lang('État du bot'), $this->view('admin/etat', $donnees)).'</div>'
				.'<div class="col-12 col-xl-6">'.$this->admin_card('fab fa-discord', $this->lang('Réglages'), $this->view('admin/reglages', $donnees)).'</div>'
			.'</div>'
			.$this->admin_card('fas fa-list', $this->lang('Journal du bot'), $donnees['journal']
				? $this->view('admin/journal', $donnees)
				: $this->admin_empty('fas fa-list', $this->lang('Le bot n’a encore rien écrit dans son journal.')), '', '', (bool) $donnees['journal']);
	}

	/**
	 * Les onglets des pages Discord (charte de l'administration : plusieurs vues sur un même sujet).
	 * Les sous-pages étaient des boutons au milieu de la carte « Réglages », mêlés aux vraies actions.
	 */
	private function _onglets(string $actif): string
	{
		$onglets = [
			'index'              => ['admin/discord',                   'fab fa-discord',      $this->lang('Vue d\'ensemble')],
			'fonctionnalites'    => ['admin/discord/fonctionnalites',   'fas fa-puzzle-piece', $this->lang('Fonctionnalités')],
			'mise-en-place'      => ['admin/discord/mise-en-place',     'fas fa-magic',        $this->lang('Mise en place du serveur')],
			'salons'             => ['admin/discord/salons',            'fas fa-exchange-alt', $this->lang('Salons et forums')],
			'roles'              => ['admin/discord/roles',             'fas fa-user-tag',     $this->lang('Groupes et rôles')],
			'roles-temporaires'  => ['admin/discord/roles-temporaires', 'fas fa-hourglass-half', $this->lang('Rôles temporaires')],
		];

		$html = '<div class="nf-local-nav">';

		foreach ($onglets as $cle => [$adresse, $icone, $titre])
		{
			$html .= '<a class="nf-local-tab'.($cle === $actif ? ' active' : '').'" href="'.url($adresse).'"><i class="'.$icone.'"></i> '.$titre.'</a>';
		}

		return $html.'</div>';
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
				$this->_modele()->enregistrer_connexion($client, $guilde, trim((string) ($post['token'] ?? '')));

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
		// Un lien d'action (révoque la clé en service, en crée une) : il porte le jeton de session.
		$this->check_csrf('admin/discord');

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
			.'<textarea class="form-control font-monospace mb-3" rows="3" readonly="readonly" aria-label="'.$this->lang('Configuration du bot').'">'.nf_texte($fichier).'</textarea>'
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
		else if ($commande === 'resynchroniser')
		{
			$modele->commander('resync');
			notify($this->lang('Le bot resynchronise tout dans la minute : son journal en rendra compte.'));
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
		$forums = array_map(static fn (array $f): string => $f['title'], $this->_forums_du_site());

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
				'etiquettes' => url('admin/discord/etiquettes/'.$s['mapping_id']),
			];
		}

		return $this->_onglets('salons')
			.$this->_correspondances($this->lang('Salons reliés'), $lignes, [$this->lang('Salon Discord'), $this->lang('Forum du site'), $this->lang('Synchronisation')], $this->lang('Aucun salon relié pour le moment.'))
			.$this->admin_card('fas fa-link', $this->lang('Relier un salon'), $this->form()->display());
	}

	/**
	 * Les étiquettes d'un salon Forum relié : chaque préfixe du forum du site a son étiquette sur
	 * Discord. Le bot pose l'étiquette quand le préfixe change sur le site, et l'inverse. La mise en
	 * place du serveur remplit déjà cette page ; à défaut, l'étiquette du même nom est proposée.
	 */
	public function _etiquettes($salon)
	{
		$this->title($this->lang('Préfixes et étiquettes'))->icon('fas fa-tags')->breadcrumb();

		$modele    = $this->_modele();
		$prefixes  = $this->_prefixes_du_site();
		$instantane = array_values(array_filter((array) ($this->_guilde($modele)['channels'] ?? []), static fn (array $c): bool => (string) $c['id'] === $salon['channel_id']))[0] ?? NULL;
		$tags      = [];

		foreach ((array) ($instantane['tags'] ?? []) as $t)
		{
			$tags[(string) $t['id']] = (string) $t['name'];
		}

		if (!$prefixes || !$tags)
		{
			return $this->admin_back('admin/discord/salons').$this->admin_card('fas fa-tags', $this->lang('Préfixes et étiquettes'), $this->admin_empty('fas fa-tags',
				!$prefixes ? $this->lang('Le forum n’a aucun préfixe.') : $this->lang('Ce salon Forum n’a aucune étiquette, ou le bot ne l’a pas encore décrit.'),
				!$prefixes ? $this->lang('Les préfixes se créent dans l’administration du forum (bouton « Préfixes »).') : $this->lang('Ajoutez des étiquettes au salon sur Discord, ou relancez la mise en place du serveur : elle crée une étiquette par préfixe.')));
		}

		$actuelles = [];

		foreach ($modele->etiquettes() as $e)
		{
			if ($e['channel_id'] === $salon['channel_id'])
			{
				$actuelles[$e['prefix_id']] = $e['tag_id'];
			}
		}

		$regles = [];

		foreach ($prefixes as $p)
		{
			$meme = array_search(mb_strtolower((string) $p['title']), array_map('mb_strtolower', $tags), TRUE);

			$regles['prefixe_'.$p['prefix_id']] = [
				'label'  => (string) $p['title'],
				'type'   => 'select',
				'values' => ['' => $this->lang('— Aucune —')] + $tags,
				'value'  => $actuelles[$p['prefix_id']] ?? ($meme !== FALSE ? (string) $meme : ''),
			];
		}

		$this->form()->add_rules($regles)->add_submit($this->lang('Enregistrer'), 'fas fa-check')->add_back('admin/discord/salons');

		if ($this->form()->is_valid($post))
		{
			$paires = [];

			foreach ($prefixes as $p)
			{
				$choisie = (string) ($post['prefixe_'.$p['prefix_id']] ?? '');

				if ($choisie !== '' && isset($tags[$choisie]))
				{
					$paires[(int) $p['prefix_id']] = $choisie;
				}
			}

			if (count($paires) !== count(array_unique($paires)))
			{
				notify($this->lang('Une étiquette ne peut valoir que pour un seul préfixe.'), 'danger');
			}
			else
			{
				$modele->remplacer_etiquettes((string) $salon['channel_id'], $paires);
				notify($this->lang('Étiquettes enregistrées : le bot les applique dans la minute.'));
				redirect('admin/discord/salons');
			}
		}

		return $this->admin_back('admin/discord/salons')
			.$this->admin_card('fas fa-tags', $this->lang('Préfixes et étiquettes'), '<p class="text-muted small">'.$this->lang('Le préfixe d’un sujet devient l’étiquette de son fil sur Discord, et l’étiquette posée sur Discord devient le préfixe du sujet.').'</p>'.$this->form()->display());
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

		$groupes = $this->_groupes_du_site();

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

		return $this->_onglets('roles')
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

	/**
	 * Les rôles temporaires en cours, donnés sur Discord par `/role give` (fonctionnalité « Rôles
	 * temporaires ») : le bot les retire à l'échéance ; « Retirer maintenant » avance l'échéance.
	 */
	public function _roles_temporaires()
	{
		$this->title($this->lang('Rôles temporaires'))->icon('fas fa-hourglass-half')->breadcrumb();

		$modele = $this->_modele();
		$roles  = array_column((array) ($this->_guilde($modele)['roles'] ?? []), 'name', 'id');
		$lignes = [];

		foreach ($modele->roles_temporaires() as $r)
		{
			$lie = $modele->membre_lie($r['discord_id']);

			$lignes[] = [
				'membre'  => nf_texte($r['username'] !== '' ? $r['username'] : $r['discord_id']).($lie ? ' <span class="text-muted small">('.$this->user->link($lie['user_id'], $lie['username']).')</span>' : ''),
				'role'    => (string) ($roles[$r['role_id']] ?? '@'.$r['role_id']),
				'fin'     => timetostr($this->lang('d/m/Y H:i'), $r['expires_at']),
				'echu'    => $r['expires_at'] <= time(),
				'par'     => $r['given_by_name'],
				'raison'  => $r['reason'],
				'retirer' => $this->csrf_url('admin/discord/roles-temporaires/retirer/'.$r['timed_id']),
			];
		}

		$active = !empty($modele->config_fonctionnalites()['roles-temporaires']['enabled']);
		$aide   = '<p class="text-muted small mb-3">'.$this->lang('Un membre qui peut gérer les rôles en donne un pour une durée avec la commande %s sur Discord ; le bot le retire à la fin, et le redonne à qui quitte puis rejoint le serveur avant.', '<code>/role give</code>').'</p>'
			.($active ? '' : '<div class="alert alert-warning small">'.$this->lang('La fonctionnalité « Rôles temporaires » est éteinte : allumez-la dans %s.', '<a href="'.url('admin/discord/fonctionnalites').'">'.$this->lang('Fonctionnalités').'</a>').'</div>');

		return $this->_onglets('roles-temporaires')
			.$this->admin_card('fas fa-hourglass-half', $this->lang('Rôles temporaires en cours'), $aide.($lignes
				? $this->view('admin/roles-temporaires', ['lignes' => $lignes])
				: $this->admin_empty('fas fa-hourglass', (string) $this->lang('Aucun rôle temporaire en cours.'))));
	}

	public function _roles_temporaires_retirer($ligne)
	{
		$this->check_csrf('admin/discord/roles-temporaires');
		$this->_modele()->echoir_role_temporaire((int) $ligne['timed_id']);
		notify($this->lang('Le bot retirera ce rôle d’ici une minute.'));
		redirect('admin/discord/roles-temporaires');
	}

	// ── Les fonctionnalités ────────────────────────────────────────────────

	/** Les fonctionnalités que le bot déclare : allumées ou non, et leurs réglages. */
	public function _fonctionnalites()
	{
		$this->title($this->lang('Fonctionnalités du bot'))->icon('fas fa-puzzle-piece')->breadcrumb();

		$modele = $this->_modele();
		$config = $modele->config_fonctionnalites();
		$cartes = '';

		foreach ($modele->fonctionnalites() as $f)
		{
			$nom    = (string) $f['nom'];
			$active = !empty($config[$nom]['enabled']);

			$cartes .= $this->view('admin/fonctionnalite', [
				'titre'       => $this->_t((string) $f['titre']),
				'description' => $this->_t((string) $f['description']),
				'nom'         => $nom,
				'active'      => $active,
				'reglages'    => count((array) $f['reglages']),
				'basculer'    => $this->csrf_url('admin/discord/fonctionnalite/'.$nom.'/basculer'),
			]);
		}

		return $this->_onglets('fonctionnalites')
			.$this->admin_card('fas fa-puzzle-piece', $this->lang('Fonctionnalités du bot'), $cartes !== ''
				? '<p class="text-muted small">'.$this->lang('Le bot déclare ses fonctionnalités à chaque signe de vie : une fonctionnalité ajoutée au bot apparaît ici. Un changement s’applique dans la minute, sans redémarrer.').'</p>'.$cartes
				: $this->admin_empty('fas fa-puzzle-piece', $this->lang('Le bot n’a encore déclaré aucune fonctionnalité.'), $this->lang('Elles apparaissent ici dès son premier signe de vie.')));
	}

	/** Allumer ou éteindre une fonctionnalité. */
	public function _basculer($fonctionnalite)
	{
		$this->check_csrf('admin/discord/fonctionnalites');

		$modele = $this->_modele();
		$active = !empty($modele->config_fonctionnalites()[$fonctionnalite['nom']]['enabled']);

		$modele->regler_fonctionnalite((string) $fonctionnalite['nom'], !$active);
		(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.fonctionnalite', ['details' => $fonctionnalite['nom'].' : '.($active ? 'off' : 'on')]);

		notify($active ? $this->lang('Fonctionnalité éteinte : le bot l’arrête dans la minute.') : $this->lang('Fonctionnalité allumée : le bot la démarre dans la minute.'));
		redirect('admin/discord/fonctionnalites');
	}

	/** Les réglages d'une fonctionnalité, en formulaire tiré de ce qu'elle déclare. */
	public function _fonctionnalite($fonctionnalite)
	{
		$titre = $this->_t((string) $fonctionnalite['titre']);

		$this->title($titre)->icon('fas fa-sliders-h')->breadcrumb();

		$modele  = $this->_modele();
		$valeurs = $modele->config_fonctionnalites()[$fonctionnalite['nom']]['settings'] ?? [];
		$guilde  = $this->_guilde($modele);
		$regles  = [];

		foreach ((array) $fonctionnalite['reglages'] as $r)
		{
			$cle   = (string) $r['cle'];
			$libelle = $this->_t((string) $r['libelle']);
			$aide  = (string) $r['aide'] !== '' ? $this->_t((string) $r['aide']) : '';
			$valeur  = $valeurs[$cle] ?? $r['defaut'];
			$regle = ['label' => $libelle] + ($aide !== '' ? ['description' => $aide] : []);

			switch ($r['type'])
			{
				case 'bool':
					$regles[$cle] = ['label' => $libelle, 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $libelle], 'checked' => ['1' => (bool) $valeur]] + ($aide !== '' ? ['description' => $aide] : []);
					break;

				case 'int':
					$regles[$cle] = $regle + ['type' => 'number', 'value' => (string) (int) $valeur, 'rules' => 'required'];
					break;

				case 'choix':
					$choix = [];

					foreach ((array) $r['choix'] as $c)
					{
						$choix[(string) $c['valeur']] = $this->_t((string) $c['libelle']);
					}

					$regles[$cle] = $regle + ['type' => 'select', 'values' => $choix, 'value' => (string) $valeur];
					break;

				case 'salon':
					$salons = ['' => $this->lang('— Aucun —')];

					foreach ((array) ($guilde['channels'] ?? []) as $c)
					{
						if (in_array((int) $c['type'], (array) $r['salons'], TRUE) || !$r['salons'])
						{
							$salons[(string) $c['id']] = (string) $c['name'];
						}
					}

					$regles[$cle] = $regle + ['type' => 'select', 'values' => $salons, 'value' => (string) $valeur];
					break;

				case 'role':
					$roles = ['' => $this->lang('— Aucun —')];

					foreach ((array) ($guilde['roles'] ?? []) as $x)
					{
						if (empty($x['managed']) && (string) $x['name'] !== '@everyone')
						{
							$roles[(string) $x['id']] = (string) $x['name'];
						}
					}

					$regles[$cle] = $regle + ['type' => 'select', 'values' => $roles, 'value' => (string) $valeur];
					break;

				default:
					$regles[$cle] = $regle + ['type' => 'text', 'value' => (string) $valeur];
			}
		}

		if (!$regles)
		{
			return $this->admin_back('admin/discord/fonctionnalites').$this->admin_card('fas fa-sliders-h', $titre, $this->admin_empty('fas fa-sliders-h', $this->lang('Cette fonctionnalité n’a pas de réglages.')));
		}

		$this->form()->add_rules($regles)->add_submit($this->lang('Enregistrer'), 'fas fa-check')->add_back('admin/discord/fonctionnalites');

		if ($this->form()->is_valid($post))
		{
			$reglages = [];
			$erreurs  = [];

			foreach ((array) $fonctionnalite['reglages'] as $r)
			{
				$cle   = (string) $r['cle'];
				$brute = $post[$cle] ?? NULL;

				switch ($r['type'])
				{
					case 'bool':
						$reglages[$cle] = in_array('1', (array) $brute, TRUE);
						break;

					case 'int':
						$n = filter_var($brute, FILTER_VALIDATE_INT);

						if ($n === FALSE || ($r['min'] !== NULL && $n < $r['min']) || ($r['max'] !== NULL && $n > $r['max']))
						{
							$erreurs[] = $this->lang('« %s » : un nombre entre %d et %d.', $this->_t((string) $r['libelle']), (int) $r['min'], (int) $r['max']);
						}
						else
						{
							$reglages[$cle] = $n;
						}
						break;

					case 'choix':
						$valides = array_map(static fn ($c): string => (string) $c['valeur'], (array) $r['choix']);
						$reglages[$cle] = in_array((string) $brute, $valides, TRUE) ? (string) $brute : $r['defaut'];
						break;

					case 'salon':
					case 'role':
						$reglages[$cle] = ctype_digit((string) $brute) ? (string) $brute : '';
						break;

					default:
						$reglages[$cle] = mb_substr(trim((string) $brute), 0, 500);
				}
			}

			if ($erreurs)
			{
				notify(implode(' ', array_map('strval', $erreurs)), 'danger');
			}
			else
			{
				$modele->regler_fonctionnalite((string) $fonctionnalite['nom'], NULL, $reglages);
				(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.fonctionnalite', ['details' => $fonctionnalite['nom'].' : settings']);
				notify($this->lang('Réglages enregistrés : le bot les applique dans la minute.'));
				redirect('admin/discord/fonctionnalites');
			}
		}

		return $this->admin_back('admin/discord/fonctionnalites').$this->admin_card('fas fa-sliders-h', $titre, $this->form()->display());
	}

	// ── La mise en place du serveur ────────────────────────────────────────

	/**
	 * Choisir ce que le bot crée sur le serveur : une catégorie, un salon Forum par forum du site, un
	 * rôle par groupe. Un aperçu suit, puis l'application ; la dernière mise en place s'annule.
	 */
	public function _mise_en_place()
	{
		$this->title($this->lang('Mise en place du serveur'))->icon('fas fa-magic')->breadcrumb();

		$modele = $this->_modele();
		$forums = $this->_forums_du_site();
		$relies = array_column($modele->salons(), 'forum_id');
		$groupes = $this->_groupes_du_site();
		$deja    = array_column($modele->roles(), 'group_key');

		$this->form()
			 ->add_rules([
				'categorie' => ['label' => $this->lang('Catégorie des salons'), 'type' => 'text', 'value' => (string) $this->config->nf_name,
				                'description' => $this->lang('Reprise si elle existe déjà ; vide : les salons sont créés hors catégorie.')],
				'forums'    => ['label' => $this->lang('Un salon Forum pour'), 'type' => 'checkbox', 'values' => array_map(static fn (array $f): string => $f['title'], $forums),
				                'checked' => array_fill_keys(array_diff(array_keys($forums), $relies), TRUE),
				                'description' => $this->lang('Les préfixes du forum deviennent les étiquettes du salon. Un salon du même nom est repris au lieu d’être dédoublé.')],
				'groupes'   => ['label' => $this->lang('Un rôle pour'), 'type' => 'checkbox', 'values' => $groupes,
				                'checked' => [],
				                'description' => $this->lang('Un rôle du même nom est repris. Les rôles créés sont placés sous celui du bot : il peut les donner.')],
			 ])
			 ->add_submit($this->lang('Voir l’aperçu'), 'fas fa-eye');

		if ($this->form()->is_valid($post))
		{
			$choisis_forums  = array_values(array_intersect(array_map('intval', (array) ($post['forums'] ?? [])), array_keys($forums)));
			$choisis_groupes = array_values(array_intersect(array_map('strval', (array) ($post['groupes'] ?? [])), array_map('strval', array_keys($groupes))));

			if (!$choisis_forums && !$choisis_groupes)
			{
				notify($this->lang('Choisissez au moins un forum ou un groupe.'), 'danger');
			}
			else
			{
				// Le plan part sur Discord : des noms en clair, pas encodés pour le web comme le site les range.
				$clair      = static fn (mixed $texte): string => html_entity_decode((string) $texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$etiquettes = array_values(array_map(static fn (array $p): array => ['prefix_id' => (int) $p['prefix_id'], 'nom' => $clair($p['title'])], $this->_prefixes_du_site()));

				$modele->poser_etat('setup-draft', (string) json_encode([
					'categorie' => mb_substr(trim($clair($post['categorie'] ?? '')), 0, 100),
					'salons'    => array_map(static fn (int $id): array => ['forum_id' => $id, 'nom' => $clair($forums[$id]['title']), 'description' => $clair($forums[$id]['description']), 'etiquettes' => $etiquettes], $choisis_forums),
					'roles'     => array_map(fn (string $cle): array => ['group_key' => $cle, 'nom' => $clair($groupes[$cle]), 'couleur' => $this->_couleur_du_groupe($cle)], $choisis_groupes),
				], JSON_UNESCAPED_UNICODE));

				redirect('admin/discord/mise-en-place/apercu');
			}
		}

		$derniere = $modele->derniere_mise_en_place();
		$attente  = (string) $modele->etat('setup-pending') !== '';

		return $this->_onglets('mise-en-place')
			.($attente ? '<div class="alert alert-info">'.icon('fas fa-hourglass-half').' '.$this->lang('Une mise en place attend le bot : il l’applique à son prochain signe de vie, et son journal en rendra compte.').'</div>' : '')
			.($derniere ? $this->admin_card('fas fa-history', $this->lang('Dernière mise en place'), $this->view('admin/mise-en-place-derniere', [
				'derniere' => $derniere,
				'annuler'  => $this->csrf_url('admin/discord/mise-en-place/annuler'),
			])) : '')
			.$this->admin_card('fas fa-magic', $this->lang('Mise en place du serveur'), $this->form()->display());
	}

	/** L'aperçu : ce qui sera créé, et ce qui sera repris parce qu'il existe déjà. */
	public function _mise_en_place_apercu()
	{
		$this->title($this->lang('Aperçu de la mise en place'))->icon('fas fa-eye')->breadcrumb();

		$modele    = $this->_modele();
		$brouillon = json_decode((string) $modele->etat('setup-draft'), TRUE);

		if (!is_array($brouillon) || !$brouillon)
		{
			redirect('admin/discord/mise-en-place');

			return '';
		}

		$guilde = $this->_guilde($modele);
		$lignes = [];

		if (($brouillon['categorie'] ?? '') !== '')
		{
			$existe   = (bool) array_filter((array) ($guilde['channels'] ?? []), static fn (array $c): bool => (int) $c['type'] === 4 && mb_strtolower((string) $c['name']) === mb_strtolower((string) $brouillon['categorie']));
			$lignes[] = ['quoi' => $this->lang('Catégorie'), 'nom' => (string) $brouillon['categorie'], 'repris' => $existe, 'detail' => ''];
		}

		foreach ((array) ($brouillon['salons'] ?? []) as $s)
		{
			$nom      = Discord::nom_de_salon((string) $s['nom']);
			$existant = array_values(array_filter((array) ($guilde['channels'] ?? []), static fn (array $c): bool => (int) $c['type'] === 15 && (string) $c['name'] === $nom))[0] ?? NULL;
			$noms     = array_map(static fn ($e): string => is_array($e) ? (string) $e['nom'] : (string) $e, (array) $s['etiquettes']);
			$ajouts   = $existant ? array_values(array_diff(array_map('mb_strtolower', $noms), array_map(static fn (array $t): string => mb_strtolower((string) $t['name']), (array) ($existant['tags'] ?? [])))) : $noms;
			$lignes[] = ['quoi' => $this->lang('Salon Forum'), 'nom' => '#'.$nom, 'repris' => (bool) $existant, 'detail' => $ajouts ? $this->lang('Étiquettes ajoutées : %s', implode(', ', $ajouts)) : ''];
		}

		foreach ((array) ($brouillon['roles'] ?? []) as $r)
		{
			$existe   = (bool) array_filter((array) ($guilde['roles'] ?? []), static fn (array $x): bool => (string) $x['name'] === (string) $r['nom'] && empty($x['managed']));
			$lignes[] = ['quoi' => $this->lang('Rôle'), 'nom' => '@'.(string) $r['nom'], 'repris' => $existe, 'detail' => ''];
		}

		$vie      = (array) json_decode((string) $modele->etat('heartbeat'), TRUE);
		$en_ligne = isset($vie['at']) && time() - (int) $vie['at'] <= self::HORS_LIGNE_APRES && !empty($vie['connected']);

		return $this->admin_back('admin/discord/mise-en-place')
			.$this->admin_card('fas fa-eye', $this->lang('Aperçu de la mise en place'), $this->view('admin/mise-en-place-apercu', [
				'lignes'    => $lignes,
				'en_ligne'  => $en_ligne,
				'appliquer' => $this->csrf_url('admin/discord/mise-en-place/appliquer'),
			]));
	}

	public function _mise_en_place_appliquer()
	{
		$this->check_csrf('admin/discord/mise-en-place');

		$modele    = $this->_modele();
		$brouillon = json_decode((string) $modele->etat('setup-draft'), TRUE);

		if (is_array($brouillon) && $brouillon)
		{
			$id = (string) time();

			$modele->commander('setup', ['id' => $id] + $brouillon);
			$modele->poser_etat('setup-pending', $id);
			$modele->poser_etat('setup-draft', '');
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.mise_en_place', ['details' => 'channels: '.count((array) ($brouillon['salons'] ?? [])).', roles: '.count((array) ($brouillon['roles'] ?? []))]);

			notify($this->lang('Le bot met le serveur en place dans la minute : son journal en rendra compte.'));
		}

		redirect('admin/discord/mise-en-place');
	}

	/** Annuler la dernière mise en place : le bot supprime ce qu'il avait créé — et seulement cela. */
	public function _mise_en_place_annuler()
	{
		$this->check_csrf('admin/discord/mise-en-place');

		$modele   = $this->_modele();
		$derniere = $modele->derniere_mise_en_place();

		if ($derniere)
		{
			$modele->commander('setup-undo', ['id' => (string) $derniere['id']] + (array) $derniere['cree']);
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('discord.mise_en_place', ['details' => 'undo '.$derniere['id']]);

			notify($this->lang('Le bot supprime ce qu’il avait créé dans la minute : son journal en rendra compte.'));
		}

		redirect('admin/discord/mise-en-place');
	}

	/** @return array<int, array{title: string, description: string}> les forums du site qui peuvent avoir un salon (pas les liens) */
	private function _forums_du_site(): array
	{
		$forums       = [];
		$forum        = $this->module('forum');
		$modele_forum = $forum ? $forum->model('forum') : NULL;

		if ($modele_forum instanceof \NF\Modules\Forum\Models\Forum)
		{
			foreach ((array) $this->db	->select('f.forum_id', $modele_forum->titre_forum('f').' AS title', $modele_forum->titre_forum('f', 'description').' AS description', 'u.url')
										->from('nf_forum f')
										->join('nf_forum_url u', 'u.forum_id = f.forum_id', 'LEFT')
										->order_by('f.order', 'f.forum_id')
										->get() as $f)
			{
				if ((string) ($f['url'] ?? '') === '')
				{
					$forums[(int) $f['forum_id']] = ['title' => (string) $f['title'], 'description' => trim(strip_tags((string) $f['description']))];
				}
			}
		}

		return $forums;
	}

	/** @return array<int, array{prefix_id: int, title: string}> les préfixes du forum */
	private function _prefixes_du_site(): array
	{
		$forum        = $this->module('forum');
		$modele_forum = $forum ? $forum->model('forum') : NULL;

		return $modele_forum instanceof \NF\Modules\Forum\Models\Forum ? $modele_forum->prefixes() : [];
	}

	/** @return array<string, string> les groupes du site (clé => titre), sans les visiteurs */
	private function _groupes_du_site(): array
	{
		$groupes = [];
		$coeur   = NeoFrag()->groups;

		foreach ($coeur instanceof \NF\NeoFrag\Core\Groups ? (array) $coeur() : [] as $cle => $g)
		{
			if ($cle !== 'visitors')
			{
				$groupes[(string) $cle] = (string) ($g['title'] ?? $cle);
			}
		}

		return $groupes;
	}

	/** La couleur d'un groupe en hexadécimal, pour le rôle qui lui correspond (NULL : la couleur par défaut de Discord). */
	private function _couleur_du_groupe(string $cle): ?string
	{
		$module = $this->module('discord');

		return $module instanceof Discord ? ($module->groupes_pour_discord()[$cle]['couleur'] ?? NULL) : NULL;
	}

	/** Un texte déclaré par le bot, traduit si le module le connaît. */
	private function _t(string $modele): string
	{
		$module = $this->module('discord');

		return $module instanceof Discord ? ($module->traduire_journal($modele, []) ?? $modele) : $modele;
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
