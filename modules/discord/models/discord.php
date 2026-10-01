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
	 * SMTP) — choix de le mainteneur, le 2026-10-01 : la changer, démarrer et arrêter le bot sans toucher à la
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

	/** Enregistre la connexion ; une clé vide garde la clé déjà enregistrée. */
	public function enregistrer_connexion(string $client_id, string $guild_id, string $jeton, bool $pseudos): void
	{
		$this->_poser('nf_discord_client_id', $client_id);
		$this->_poser('nf_discord_guild_id', $guild_id);
		$this->_poser('nf_discord_nicknames', $pseudos ? '1' : '0', 'bool');

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

	/** Une commande pour le bot (« restart »…), qu'il prendra à son prochain signe de vie. */
	public function commander(string $commande): void
	{
		$commandes   = (array) json_decode((string) $this->etat('commands'), TRUE);
		$commandes[] = $commande;

		$this->poser_etat('commands', json_encode(array_values(array_unique($commandes))));
	}

	/** @return list<string> les commandes en attente, retirées de la file */
	public function prendre_commandes(): array
	{
		$commandes = array_values(array_filter((array) json_decode((string) $this->etat('commands'), TRUE), 'is_string'));

		if ($commandes)
		{
			$this->poser_etat('commands', '[]');
		}

		return $commandes;
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
		$this->db->where('mapping_id', $mapping_id)->delete('nf_discord_channels');
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

	/** Le fil Discord d'un sujet, le message Discord d'un message — et l'inverse. */
	public function lier(string $type, int $site_id, string $discord_id): void
	{
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
