<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Discord\Models;

use NF\NeoFrag\Loadables\Model;

class Discord extends Model
{
	/*
	 * Le bot Discord. Les réglages vivent dans `nf_settings` (`nf_discord_*`) ; la clé
	 * Discord y est CHIFFRÉE avec la clé propre au site (`Crypt::encrypt_secret`, comme le mot de passe
	 * SMTP) — choix du 2026-10-01 : la changer, démarrer et arrêter le bot sans toucher à la
	 * machine qui le fait tourner. Chaque changement de réglage augmente `nf_discord_version` : le bot
	 * relit sa configuration quand ce numéro change.
	 */

	/** Durée de vie d'une ligne du journal du bot, en jours. */
	const RETENTION = 14;

	/** @return array{client_id: string, guild_id: string, running: bool, nicknames: bool, token_set: bool, version: int} */
	public function reglages(): array
	{
		return [
			'client_id' => $this->_lire('client_id'),
			'guild_id'  => $this->_lire('guild_id'),
			'running'   => (bool) $this->_lire('running'),
			'nicknames' => (bool) $this->_lire('nicknames'),
			'token_set' => $this->_lire('token') !== '',
			'version'   => (int) $this->_lire('version'),
		];
	}

	/** La clé Discord, déchiffrée — pour le bot seulement, par l'API. */
	public function jeton(): string
	{
		$chiffre = $this->_lire('token');

		return $chiffre !== '' ? (string) $this->crypt->decrypt_secret($chiffre) : '';
	}

	/**
	 * Enregistre la connexion ; une clé vide garde la clé déjà enregistrée. (Les pseudos, réglés ici en
	 * 1.2.11, sont depuis un réglage de la fonctionnalité « roles ».)
	 */
	public function enregistrer_connexion(string $client_id, string $guild_id, string $jeton): void
	{
		$this->_poser('nf_discord_client_id', $client_id);
		$this->_poser('nf_discord_guild_id', $guild_id);

		if ($jeton !== '')
		{
			$this->_poser('nf_discord_token', (string) $this->crypt->encrypt_secret($jeton));
		}

		$this->nouvelle_version();
	}

	public function mettre_en_marche(bool $marche): void
	{
		$this->_poser('nf_discord_running', $marche ? '1' : '0', 'bool');
		$this->nouvelle_version();
	}

	/** Le bot relit sa configuration au prochain signe de vie. */
	public function nouvelle_version(): void
	{
		$this->_poser('nf_discord_version', (string) ((int) $this->_lire('version') + 1), 'int');
	}

	// ── Les commandes, l'état et le journal ────────────────────────────────

	/**
	 * Une commande pour le bot, qu'il prendra à son prochain signe de vie : `restart`, `resync`,
	 * `setup` (avec son plan), `setup-undo`… La même commande avec les mêmes données n'est gardée
	 * qu'une fois.
	 *
	 * @param array<string, mixed> $donnees
	 */
	public function commander(string $type, array $donnees = []): void
	{
		$commandes = $this->_commandes();
		$nouvelle  = ['type' => $type, 'data' => $donnees];

		if (!in_array($nouvelle, $commandes, FALSE))
		{
			$commandes[] = $nouvelle;
		}

		$this->poser_etat('commands', (string) json_encode($commandes, JSON_UNESCAPED_UNICODE));
	}

	/** @return list<array{type: string, data: array<string, mixed>}> les commandes en attente, retirées de la file */
	public function prendre_commandes(): array
	{
		$commandes = $this->_commandes();

		if ($commandes)
		{
			$this->poser_etat('commands', '[]');
		}

		return $commandes;
	}

	/** @return list<array{type: string, data: array<string, mixed>}> */
	private function _commandes(): array
	{
		$commandes = [];

		foreach ((array) json_decode((string) $this->etat('commands'), TRUE) as $c)
		{
			// Une commande d'avant la 1.2.12 n'était qu'un nom.
			if (is_string($c))
			{
				$commandes[] = ['type' => $c, 'data' => []];
			}
			else if (is_array($c) && is_string($c['type'] ?? NULL))
			{
				$commandes[] = ['type' => $c['type'], 'data' => is_array($c['data'] ?? NULL) ? $c['data'] : []];
			}
		}

		return $commandes;
	}

	// ── Les fonctionnalités du bot ─────────────────────────────────────────

	/*
	 * Le bot DÉCLARE ses fonctionnalités à chaque signe de vie : nom, titre, description, réglages
	 * (type, valeur par défaut, libellé). L'administration en tire ses formulaires : une
	 * fonctionnalité ajoutée au bot y apparaît sans toucher au site. Les choix de l'administrateur —
	 * allumée ou non, valeurs des réglages — vivent dans `nf_discord_features`.
	 */

	/** Types de réglage qu'une fonctionnalité peut déclarer. */
	const TYPES_DE_REGLAGE = ['bool', 'int', 'texte', 'choix', 'salon', 'role'];

	/** @return list<array<string, mixed>> les fonctionnalités telles que le bot les a déclarées en dernier */
	public function fonctionnalites(): array
	{
		return array_values(array_filter((array) json_decode((string) $this->etat('features'), TRUE), 'is_array'));
	}

	/**
	 * Garde les déclarations envoyées par le bot, nettoyées : ce sont des données venues d'un
	 * programme, qui finiront dans des formulaires.
	 *
	 * @param array<mixed> $declarations
	 */
	public function declarer_fonctionnalites(array $declarations): void
	{
		$propres = [];
		$texte   = static fn ($v, int $max): string => mb_substr(trim((string) (is_scalar($v) ? $v : '')), 0, $max);

		foreach (array_slice($declarations, 0, 30) as $f)
		{
			if (!is_array($f) || !preg_match('/^[a-z][a-z0-9_-]{0,31}$/', (string) ($f['nom'] ?? '')))
			{
				continue;
			}

			$reglages = [];

			foreach (array_slice((array) ($f['reglages'] ?? []), 0, 20) as $r)
			{
				if (!is_array($r) || !preg_match('/^[a-z][a-z0-9_]{0,31}$/', (string) ($r['cle'] ?? '')) || !in_array($r['type'] ?? '', self::TYPES_DE_REGLAGE, TRUE))
				{
					continue;
				}

				$reglages[] = [
					'cle'     => (string) $r['cle'],
					'type'    => (string) $r['type'],
					'defaut'  => is_scalar($r['defaut'] ?? NULL) ? $r['defaut'] : NULL,
					'libelle' => $texte($r['libelle'] ?? '', 200),
					'aide'    => $texte($r['aide'] ?? '', 500),
					'min'     => is_numeric($r['min'] ?? NULL) ? (int) $r['min'] : NULL,
					'max'     => is_numeric($r['max'] ?? NULL) ? (int) $r['max'] : NULL,
					'choix'   => array_values(array_filter(array_map(static fn ($c) => is_array($c) && is_scalar($c['valeur'] ?? NULL) ? ['valeur' => $texte($c['valeur'], 64), 'libelle' => $texte($c['libelle'] ?? '', 200)] : NULL, array_slice((array) ($r['choix'] ?? []), 0, 20)))),
					'salons'  => array_values(array_map('intval', array_filter((array) ($r['salons'] ?? []), 'is_numeric'))),
				];
			}

			$propres[] = [
				'nom'         => (string) $f['nom'],
				'titre'       => $texte($f['titre'] ?? '', 200),
				'description' => $texte($f['description'] ?? '', 500),
				'defaut'      => !array_key_exists('defaut', $f) || !empty($f['defaut']),
				'reglages'    => $reglages,
			];
		}

		$this->poser_etat('features', (string) json_encode($propres, JSON_UNESCAPED_UNICODE));
	}

	/** @return array<string, array{enabled?: bool, settings?: array<string, mixed>}> les choix de l'administrateur */
	public function choix_fonctionnalites(): array
	{
		$choix = array_filter((array) json_decode($this->_lire('features'), TRUE), 'is_array');

		// La case « Pseudos » de la connexion (1.2.11) vaut le réglage « pseudos » des rôles, tant que
		// l'administrateur ne l'a pas posé lui-même.
		if (!isset($choix['roles']['settings']['pseudos']) && $this->_lire('nicknames') === '1')
		{
			$choix['roles']['settings']['pseudos'] = TRUE;
		}

		return $choix;
	}

	/**
	 * Allume ou éteint une fonctionnalité, et pose ses réglages (déjà validés par l'administration).
	 *
	 * @param array<string, mixed>|null $reglages NULL : les réglages ne changent pas
	 */
	public function regler_fonctionnalite(string $nom, ?bool $actif, ?array $reglages = NULL): void
	{
		$choix = $this->choix_fonctionnalites();

		if ($actif !== NULL)
		{
			$choix[$nom]['enabled'] = $actif;
		}

		if ($reglages !== NULL)
		{
			$choix[$nom]['settings'] = $reglages;
		}

		$this->_poser('nf_discord_features', (string) json_encode($choix, JSON_UNESCAPED_UNICODE));
		$this->nouvelle_version();
	}

	/**
	 * Ce que le bot reçoit pour chaque fonctionnalité déclarée : allumée ou non, et ses réglages
	 * (valeur choisie, sinon la valeur par défaut qu'elle a déclarée).
	 *
	 * @return array<string, array{enabled: bool, settings: array<string, mixed>}>
	 */
	public function config_fonctionnalites(): array
	{
		$choix  = $this->choix_fonctionnalites();
		$config = [];

		foreach ($this->fonctionnalites() as $f)
		{
			$nom      = (string) $f['nom'];
			$reglages = [];

			foreach ((array) $f['reglages'] as $r)
			{
				$reglages[$r['cle']] = $choix[$nom]['settings'][$r['cle']] ?? $r['defaut'];
			}

			$config[$nom] = ['enabled' => (bool) ($choix[$nom]['enabled'] ?? $f['defaut']), 'settings' => $reglages];
		}

		return $config;
	}

	/** @return list<string> les textes que le bot poste sur Discord, tels qu'il les a déclarés en dernier */
	public function textes_demandes(): array
	{
		return array_values(array_filter((array) json_decode((string) $this->etat('texts'), TRUE), 'is_string'));
	}

	/** @param list<string> $textes */
	public function demander_textes(array $textes): void
	{
		$this->poser_etat('texts', (string) json_encode(array_slice(array_values(array_unique(array_map(static fn (string $t): string => mb_substr($t, 0, 500), $textes))), 0, 300), JSON_UNESCAPED_UNICODE));
	}

	// ── Relier son compte depuis Discord (`/forum account link`) ───────────

	/** Minutes pendant lesquelles un lien de liaison reste valable. */
	const LIAISON_MINUTES = 15;

	/**
	 * Un lien à usage unique pour relier ce compte Discord à un compte du site : le bot l'envoie au
	 * membre, qui l'ouvre connecté. Rend le jeton (le site n'en garde que l'empreinte).
	 */
	public function demander_liaison(string $discord_id, string $pseudo, ?string $avatar): string
	{
		$jeton = bin2hex(random_bytes(20));

		// Les liens échus ne servent plus : le ménage se fait ici, à la demande suivante.
		$this->db->where('expires_at <', date('Y-m-d H:i:s'))->delete('nf_discord_link_tokens');

		$this->db->insert('nf_discord_link_tokens', [
			'token_hash' => hash('sha256', $jeton),
			'discord_id' => $discord_id,
			'username'   => mb_substr($pseudo, 0, 100),
			'avatar'     => $avatar !== NULL && preg_match('#^https://(cdn|media)\.discordapp\.(com|net)/#', $avatar) ? mb_substr($avatar, 0, 255) : NULL,
			'expires_at' => date('Y-m-d H:i:s', time() + self::LIAISON_MINUTES * 60),
		]);

		return $jeton;
	}

	/** @return array{discord_id: string, username: string, avatar: ?string}|null un lien de liaison valable (ni échu, ni déjà servi) */
	public function liaison(string $jeton): ?array
	{
		if (!preg_match('/^[0-9a-f]{40}$/', $jeton))
		{
			return NULL;
		}

		$ligne = $this->db	->select('discord_id', 'username', 'avatar')
							->from('nf_discord_link_tokens')
							->where('token_hash', hash('sha256', $jeton))
							->where('used_at', NULL)
							->where('expires_at >', date('Y-m-d H:i:s'))
							->row();

		return is_array($ligne) && $ligne ? ['discord_id' => (string) $ligne['discord_id'], 'username' => (string) $ligne['username'], 'avatar' => $ligne['avatar'] !== NULL ? (string) $ligne['avatar'] : NULL] : NULL;
	}

	public function consommer_liaison(string $jeton): void
	{
		$this->db->where('token_hash', hash('sha256', $jeton))->update('nf_discord_link_tokens', ['used_at' => date('Y-m-d H:i:s')]);
	}

	/** L'identifiant de l'authentificateur Discord (la connexion par Discord), ou NULL s'il n'est pas installé. */
	public function authentificateur(): ?int
	{
		$id = $this->db	->select('a.id')
						->from('nf_addon a')
						->join('nf_addon_type t', 't.id = a.type_id', 'INNER')
						->where('t.name', 'authenticator')
						->where('a.name', 'discord')
						->row();

		return $id ? (int) $id : NULL;
	}

	/** @return array{user_id: int, username: string}|null le membre qui a lié ce compte Discord */
	public function membre_lie(string $discord_id): ?array
	{
		$authentificateur = $this->authentificateur();
		$membre           = $authentificateur ? $this->db	->select('u.id', 'u.username')
															->from('nf_user_auth a')
															->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
															->where('a.authenticator_id', $authentificateur)
															->where('a.key', $discord_id)
															->row() : NULL;

		return is_array($membre) && $membre ? ['user_id' => (int) $membre['id'], 'username' => (string) $membre['username']] : NULL;
	}

	/**
	 * Relie un compte Discord à un membre. Refusé si ce Discord est déjà lié à un autre membre
	 * (`discord_taken`), ou si ce membre a déjà un autre Discord (`member_has_discord` : il le délie
	 * d'abord, depuis « Mes comptes liés »).
	 *
	 * @return array{ok: bool, erreur?: string}
	 */
	public function lier_compte(int $user_id, string $discord_id, string $pseudo): array
	{
		$authentificateur = $this->authentificateur();

		if (!$authentificateur)
		{
			return ['ok' => FALSE, 'erreur' => 'no_authenticator'];
		}

		$deja = $this->membre_lie($discord_id);

		if ($deja)
		{
			return $deja['user_id'] === $user_id ? ['ok' => TRUE] : ['ok' => FALSE, 'erreur' => 'discord_taken'];
		}

		if ($this->db->select('id')->from('nf_user_auth')->where('user_id', $user_id)->where('authenticator_id', $authentificateur)->row())
		{
			return ['ok' => FALSE, 'erreur' => 'member_has_discord'];
		}

		$this->db->insert('nf_user_auth', ['user_id' => $user_id, 'authenticator_id' => $authentificateur, 'key' => $discord_id, 'username' => mb_substr($pseudo, 0, 100)]);
		$this->signaler('user.discord.linked', $user_id, $discord_id);

		return ['ok' => TRUE];
	}

	/**
	 * Délie un compte Discord de son membre (`/forum account unlink`) — sauf s'il est son seul moyen de
	 * se connecter (`only_login_method`), comme sur la page « Mes comptes liés ».
	 *
	 * @return array{ok: bool, erreur?: string, username?: string}
	 */
	public function delier_compte(string $discord_id): array
	{
		$membre = $this->membre_lie($discord_id);

		if (!$membre)
		{
			return ['ok' => FALSE, 'erreur' => 'not_linked'];
		}

		$authentificateur = (int) $this->authentificateur();
		$mot_de_passe     = (string) $this->db->select('password')->from('nf_user')->where('id', $membre['user_id'])->row();
		$autres           = (int) $this->db->select('COUNT(*)')->from('nf_user_auth')->where('user_id', $membre['user_id'])->where('authenticator_id <>', $authentificateur)->row();

		if ($mot_de_passe === '' && !$autres)
		{
			return ['ok' => FALSE, 'erreur' => 'only_login_method', 'username' => $membre['username']];
		}

		$this->db->where('user_id', $membre['user_id'])->where('authenticator_id', $authentificateur)->where('key', $discord_id)->delete('nf_user_auth');
		$this->signaler('user.discord.unlinked', $membre['user_id'], $discord_id);

		return ['ok' => TRUE, 'username' => $membre['username']];
	}

	/**
	 * Un compte Discord lié ou délié : l'événement du site, et le fil de l'API, d'où le bot apprend à
	 * donner ou retirer aussitôt les rôles de ce membre.
	 *
	 * couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
	 */
	public function signaler(string $evenement, int $user_id, string $discord_id): void
	{
		NeoFrag()->events->fire($evenement, ['user_id' => $user_id, 'discord_id' => $discord_id]);

		// Pendant une requête de l'API (`/forum account unlink`), l'API écoute elle-même cet événement
		// (Api::__init) : l'inscrire ici aussi le compterait deux fois.
		if (\NF\Modules\Api\Api::$cle_courante === NULL && ($api = \NF\NeoFrag\Addons\Module::__load(NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			$api->consigner($evenement, ['user_id' => $user_id, 'discord_id' => $discord_id]);
		}
	}

	// ── La mise en place du serveur ────────────────────────────────────────

	/**
	 * Le compte rendu d'une mise en place, envoyé par le bot : les correspondances qu'il a créées ou
	 * reprises sont posées (sauf celles qui existent déjà), et ce qu'il a CRÉÉ est retenu — c'est ce
	 * que « Annuler la dernière mise en place » supprimera.
	 *
	 * @param array<string, mixed> $compte_rendu
	 */
	public function appliquer_compte_rendu(array $compte_rendu): void
	{
		$salons = $this->salons();
		$roles  = $this->roles();

		foreach ((array) ($compte_rendu['salons'] ?? []) as $s)
		{
			$forum = (int) ($s['forum_id'] ?? 0);
			$salon = (string) ($s['channel_id'] ?? '');

			if ($forum > 0 && ctype_digit($salon) && !array_filter($salons, static fn (array $x): bool => $x['forum_id'] === $forum || $x['channel_id'] === $salon))
			{
				$this->db->insert('nf_discord_channels', ['channel_id' => $salon, 'forum_id' => $forum, 'mode' => 'all', 'emoji' => '📌']);
				$salons[] = ['mapping_id' => 0, 'channel_id' => $salon, 'forum_id' => $forum, 'mode' => 'all', 'emoji' => '📌'];
			}
		}

		foreach ((array) ($compte_rendu['roles'] ?? []) as $r)
		{
			$groupe = mb_substr((string) ($r['group_key'] ?? ''), 0, 100);
			$role   = (string) ($r['role_id'] ?? '');

			if ($groupe !== '' && ctype_digit($role) && !array_filter($roles, static fn (array $x): bool => $x['group_key'] === $groupe))
			{
				$this->db->insert('nf_discord_roles', ['group_key' => $groupe, 'role_id' => $role]);
				$roles[] = ['mapping_id' => 0, 'group_key' => $groupe, 'role_id' => $role];
			}
		}

		// Les étiquettes créées ou reprises pour les préfixes du forum (une étiquette ne vaut que pour un préfixe).
		$connues = $this->etiquettes();

		foreach ((array) ($compte_rendu['etiquettes'] ?? []) as $t)
		{
			$salon     = (string) ($t['channel_id'] ?? '');
			$prefixe   = (int) ($t['prefix_id'] ?? 0);
			$etiquette = (string) ($t['tag_id'] ?? '');

			if (ctype_digit($salon) && $prefixe > 0 && ctype_digit($etiquette) && !array_filter($connues, static fn (array $x): bool => $x['channel_id'] === $salon && ($x['prefix_id'] === $prefixe || $x['tag_id'] === $etiquette)))
			{
				$this->db->insert('nf_discord_tags', ['channel_id' => $salon, 'prefix_id' => $prefixe, 'tag_id' => $etiquette]);
				$connues[] = ['mapping_id' => 0, 'channel_id' => $salon, 'prefix_id' => $prefixe, 'tag_id' => $etiquette];
			}
		}

		$cree = (array) ($compte_rendu['cree'] ?? []);
		$ids  = static fn ($liste): array => array_values(array_filter(array_map('strval', (array) $liste), 'ctype_digit'));

		$this->poser_etat('setup-last', (string) json_encode([
			'id'      => mb_substr((string) ($compte_rendu['id'] ?? ''), 0, 40),
			'at'      => time(),
			'cree'    => ['categorie' => ctype_digit((string) ($cree['categorie'] ?? '')) ? (string) $cree['categorie'] : NULL, 'salons' => $ids($cree['salons'] ?? []), 'roles' => $ids($cree['roles'] ?? [])],
			'repris'  => count((array) ($compte_rendu['salons'] ?? [])) + count((array) ($compte_rendu['roles'] ?? [])) - count($ids($cree['salons'] ?? [])) - count($ids($cree['roles'] ?? [])),
			'erreurs' => array_slice(array_map(static fn ($e): string => mb_substr((string) $e, 0, 300), (array) ($compte_rendu['erreurs'] ?? [])), 0, 20),
		], JSON_UNESCAPED_UNICODE));
		$this->poser_etat('setup-pending', '');
		$this->nouvelle_version();
	}

	/**
	 * L'annulation faite par le bot : les correspondances vers ce qu'il a supprimé disparaissent, et la
	 * dernière mise en place est oubliée.
	 *
	 * @param list<string> $salons
	 * @param list<string> $roles
	 */
	public function annuler_compte_rendu(array $salons, array $roles): void
	{
		if ($salons)
		{
			$this->db->where('channel_id', array_values($salons))->delete('nf_discord_channels');
			$this->db->where('channel_id', array_values($salons))->delete('nf_discord_tags');
		}

		if ($roles)
		{
			$this->db->where('role_id', array_values($roles))->delete('nf_discord_roles');
		}

		$this->poser_etat('setup-last', '');
		$this->nouvelle_version();
	}

	/** @return array<string, mixed>|null la dernière mise en place, telle que le bot l'a rapportée */
	public function derniere_mise_en_place(): ?array
	{
		$derniere = json_decode((string) $this->etat('setup-last'), TRUE);

		return is_array($derniere) && $derniere ? $derniere : NULL;
	}

	/** Le dernier événement du site que le bot a traité : il reprend de là, même après un arrêt. */
	public function curseur(): ?int
	{
		$curseur = $this->etat('cursor');

		return $curseur !== NULL && ctype_digit($curseur) ? (int) $curseur : NULL;
	}

	public function etat(string $nom): ?string
	{
		$valeur = $this->db->select('value')->from('nf_discord_state')->where('name', $nom)->row();

		// `row()` rend un tableau vide quand la ligne n'existe pas, et NULL quand la valeur est vide.
		return is_array($valeur) || $valeur === NULL ? NULL : (string) $valeur;
	}

	/** @return array{value: ?string, updated_at: ?string} */
	public function etat_date(string $nom): array
	{
		$ligne = $this->db->select('value', 'updated_at')->from('nf_discord_state')->where('name', $nom)->row();

		return is_array($ligne) && $ligne ? ['value' => (string) $ligne['value'], 'updated_at' => (string) $ligne['updated_at']] : ['value' => NULL, 'updated_at' => NULL];
	}

	public function poser_etat(string $nom, string $valeur): void
	{
		$this->db->execute('INSERT INTO nf_discord_state (name, value) VALUES ("'.$this->db->escape_string($nom).'", "'.$this->db->escape_string($valeur).'") ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP');
	}

	/**
	 * Une ligne du journal du bot : la phrase française, et, pour la traduire à l'affichage, son
	 * modèle et ses valeurs (cf. `Discord::traduire_journal()`).
	 *
	 * @param list<string> $valeurs
	 */
	public function journaliser(string $niveau, string $message, string $modele = '', array $valeurs = []): void
	{
		$this->db->insert('nf_discord_logs', [
			'level'    => in_array($niveau, ['info', 'warn', 'error'], TRUE) ? $niveau : 'info',
			'message'  => mb_substr($message, 0, 1000),
			'template' => $modele !== '' ? mb_substr($modele, 0, 500) : NULL,
			'args'     => $modele !== '' ? json_encode(array_map(static fn ($v): string => mb_substr((string) $v, 0, 500), array_slice(array_values($valeurs), 0, 10)), JSON_UNESCAPED_UNICODE) : NULL,
		]);

		if (random_int(1, 50) === 1)
		{
			$this->db->where('created_at <', date('Y-m-d H:i:s', time() - self::RETENTION * 86400))->delete('nf_discord_logs');
		}
	}

	/** @return list<array{level: string, message: string, template: string, args: list<string>, created_at: string}> les plus récentes d'abord */
	public function journal(int $nombre = 50): array
	{
		return array_map(static fn (array $l): array => [
			'level'      => (string) $l['level'],
			'message'    => (string) $l['message'],
			'template'   => (string) ($l['template'] ?? ''),
			'args'       => array_values(array_map('strval', (array) json_decode((string) ($l['args'] ?? '[]'), TRUE))),
			'created_at' => (string) $l['created_at'],
		], array_values((array) $this->db->select('level', 'message', 'template', 'args', 'created_at')->from('nf_discord_logs')->order_by('log_id DESC')->limit($nombre)->get()));
	}

	/**
	 * Les textes que le bot poste sur Discord, dans la langue du site : le bot n'a pas de traductions
	 * à lui, il reçoit celles-ci avec sa configuration.
	 *
	 * @return array{on_site: string, read_more: string, published: string}
	 */
	public function textes_affiches(): array
	{
		return [
			'on_site'   => (string) $this->lang('Sur le site'),
			'read_more' => (string) $this->lang('Lire la suite sur le site'),
			'published' => (string) $this->lang('Ce fil est maintenant aussi sur le site : %s'),
		];
	}

	// ── Les correspondances ────────────────────────────────────────────────

	/** @return list<array{mapping_id: int, channel_id: string, forum_id: int, mode: string, emoji: string}> */
	public function salons(): array
	{
		return array_map(static fn (array $s): array => ['mapping_id' => (int) $s['mapping_id'], 'channel_id' => (string) $s['channel_id'], 'forum_id' => (int) $s['forum_id'], 'mode' => (string) $s['mode'], 'emoji' => (string) $s['emoji']],
			array_values((array) $this->db->select('mapping_id', 'channel_id', 'forum_id', 'mode', 'emoji')->from('nf_discord_channels')->order_by('mapping_id')->get()));
	}

	public function ajouter_salon(string $channel_id, int $forum_id, string $mode, string $emoji): void
	{
		$this->db->insert('nf_discord_channels', ['channel_id' => $channel_id, 'forum_id' => $forum_id, 'mode' => $mode === 'reaction' ? 'reaction' : 'all', 'emoji' => mb_substr($emoji, 0, 64)]);
		$this->nouvelle_version();
	}

	public function supprimer_salon(int $mapping_id): void
	{
		$salon = (string) $this->db->select('channel_id')->from('nf_discord_channels')->where('mapping_id', $mapping_id)->row();

		$this->db->where('mapping_id', $mapping_id)->delete('nf_discord_channels');

		// Ses étiquettes ne servent plus à rien : elles partent avec lui.
		if ($salon !== '')
		{
			$this->db->where('channel_id', $salon)->delete('nf_discord_tags');
		}

		$this->nouvelle_version();
	}

	/** @return list<array{mapping_id: int, group_key: string, role_id: string}> */
	public function roles(): array
	{
		return array_map(static fn (array $r): array => ['mapping_id' => (int) $r['mapping_id'], 'group_key' => (string) $r['group_key'], 'role_id' => (string) $r['role_id']],
			array_values((array) $this->db->select('mapping_id', 'group_key', 'role_id')->from('nf_discord_roles')->order_by('mapping_id')->get()));
	}

	public function ajouter_role(string $group_key, string $role_id): void
	{
		$this->db->insert('nf_discord_roles', ['group_key' => $group_key, 'role_id' => $role_id]);
		$this->nouvelle_version();
	}

	public function supprimer_role(int $mapping_id): void
	{
		$this->db->where('mapping_id', $mapping_id)->delete('nf_discord_roles');
		$this->nouvelle_version();
	}

	// ── Les rôles temporaires ───────────────────────────────────

	/** La plus longue durée d'un rôle temporaire, en secondes : un an. */
	const ROLE_TEMPORAIRE_MAX = 31536000;

	/**
	 * Les rôles temporaires en cours — tous, ceux d'un membre Discord, ou ceux arrivés à échéance, que
	 * le bot retire. L'échéance est rendue en horodatage Unix, la plus proche d'abord.
	 *
	 * @return list<array{timed_id: int, discord_id: string, username: string, role_id: string, expires_at: int, given_by: string, given_by_name: string, reason: string}>
	 */
	public function roles_temporaires(?string $discord_id = NULL, bool $echus = FALSE): array
	{
		$this->db	->select('timed_id', 'discord_id', 'username', 'role_id', 'UNIX_TIMESTAMP(expires_at) AS expires_at', 'given_by', 'given_by_name', 'reason')
					->from('nf_discord_timed_roles');

		if ($discord_id !== NULL)
		{
			$this->db->where('discord_id', $discord_id);
		}

		if ($echus)
		{
			$this->db->where('expires_at <= NOW()');
		}

		return array_map(static fn (array $r): array => [
			'timed_id'      => (int) $r['timed_id'],
			'discord_id'    => (string) $r['discord_id'],
			'username'      => (string) $r['username'],
			'role_id'       => (string) $r['role_id'],
			'expires_at'    => (int) $r['expires_at'],
			'given_by'      => (string) $r['given_by'],
			'given_by_name' => (string) $r['given_by_name'],
			'reason'        => (string) $r['reason'],
		], array_values((array) $this->db->order_by('expires_at', 'timed_id')->get(FALSE)));
	}

	/**
	 * Donne un rôle temporaire, ou le prolonge : un seul par membre et par rôle, la nouvelle échéance
	 * remplace l'ancienne. Rend son numéro.
	 */
	public function donner_role_temporaire(string $discord_id, string $pseudo, string $role_id, int $secondes, ?string $donne_par, string $donne_par_nom, string $raison): int
	{
		$texte = fn (string $v, int $max): string => $v === '' ? 'NULL' : '"'.$this->db->escape_string(mb_substr($v, 0, $max)).'"';

		$this->db->execute('INSERT INTO nf_discord_timed_roles (discord_id, username, role_id, expires_at, given_by, given_by_name, reason) VALUES ('
			.implode(', ', [$texte($discord_id, 20), $texte($pseudo, 100), $texte($role_id, 20), '"'.date('Y-m-d H:i:s', time() + $secondes).'"', $texte((string) $donne_par, 20), $texte($donne_par_nom, 100), $texte($raison, 200)])
			.') ON DUPLICATE KEY UPDATE username = VALUES(username), expires_at = VALUES(expires_at), given_by = VALUES(given_by), given_by_name = VALUES(given_by_name), reason = VALUES(reason)');

		return (int) $this->db->select('timed_id')->from('nf_discord_timed_roles')->where('discord_id', $discord_id)->where('role_id', $role_id)->row();
	}

	public function retirer_role_temporaire(int $timed_id): void
	{
		$this->db->where('timed_id', $timed_id)->delete('nf_discord_timed_roles');
	}

	/** « Retirer maintenant », dans l'administration : l'échéance passe à maintenant, le bot retire le rôle à son prochain passage. */
	public function echoir_role_temporaire(int $timed_id): void
	{
		$this->db->execute('UPDATE nf_discord_timed_roles SET expires_at = NOW() WHERE timed_id = '.$timed_id);
	}

	/** @return list<array{mapping_id: int, channel_id: string, prefix_id: int, tag_id: string}> préfixe du forum ↔ étiquette d'un salon Forum */
	public function etiquettes(): array
	{
		return array_map(static fn (array $t): array => ['mapping_id' => (int) $t['mapping_id'], 'channel_id' => (string) $t['channel_id'], 'prefix_id' => (int) $t['prefix_id'], 'tag_id' => (string) $t['tag_id']],
			array_values((array) $this->db->select('mapping_id', 'channel_id', 'prefix_id', 'tag_id')->from('nf_discord_tags')->order_by('mapping_id')->get()));
	}

	/**
	 * Les étiquettes d'un salon : préfixe => étiquette. Remplace celles qu'il avait ; une étiquette ne
	 * vaut que pour un préfixe.
	 *
	 * @param array<int, string> $paires
	 */
	public function remplacer_etiquettes(string $channel_id, array $paires): void
	{
		$this->db->where('channel_id', $channel_id)->delete('nf_discord_tags');

		$prises = [];

		foreach ($paires as $prefixe => $etiquette)
		{
			if ((int) $prefixe > 0 && ctype_digit((string) $etiquette) && !isset($prises[$etiquette]))
			{
				$prises[$etiquette] = TRUE;
				$this->db->insert('nf_discord_tags', ['channel_id' => $channel_id, 'prefix_id' => (int) $prefixe, 'tag_id' => (string) $etiquette]);
			}
		}

		$this->nouvelle_version();
	}

	/**
	 * Le fil Discord d'un sujet, le message Discord d'un message — et l'inverse. Un lien déjà posé reste, sauf à le
	 * `remplacer` : un ticket qui change de salon a un nouveau fil (bot 0.2.5, 2026-10-09).
	 */
	public function lier(string $type, int $site_id, string $discord_id, bool $remplacer = FALSE): void
	{
		if ($remplacer)
		{
			$this->db->where('type', $type)->where('site_id', $site_id)->delete('nf_discord_links');
		}

		$this->db->execute('INSERT IGNORE INTO nf_discord_links (type, site_id, discord_id) VALUES ("'.$this->db->escape_string($type).'", '.$site_id.', "'.$this->db->escape_string($discord_id).'")');
	}

	public function lien(string $type, ?int $site_id, ?string $discord_id): ?array
	{
		$this->db->select('site_id', 'discord_id')->from('nf_discord_links')->where('type', $type);

		if ($site_id !== NULL)
		{
			$this->db->where('site_id', $site_id);
		}
		else
		{
			$this->db->where('discord_id', (string) $discord_id);
		}

		$lien = $this->db->row();

		return is_array($lien) && $lien ? ['site_id' => (int) $lien['site_id'], 'discord_id' => (string) $lien['discord_id']] : NULL;
	}

	/** Un réglage du bot (`nf_discord_<nom>`), vide s'il n'a jamais été posé. */
	private function _lire(string $nom): string
	{
		return (string) $this->config->{'nf_discord_'.$nom};
	}

	/** Un réglage du site (créé au besoin, et relu à jour dans la même requête : cf. `Config::__invoke`). */
	private function _poser(string $nom, string $valeur, string $type = 'string'): void
	{
		$config = NeoFrag()->config;
		$config($nom, $valeur, $type);
	}
}
