<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Discord — le bot Discord de NeoFrag Reborn, réglé depuis l'administration.
 *
 * Le bot est un programme à part (`bot/` du dépôt), qui tourne sur une machine allumée en permanence.
 * Il ne connaît que l'adresse du site et une clé de l'API : tout le reste — sa clé Discord, chiffrée,
 * son serveur, les correspondances salon ↔ forum et groupe ↔ rôle, l'interrupteur « en marche » —
 * se règle ici, et il le relit par l'API. Il y renvoie son état et son journal.
 */

namespace NF\Modules\Discord;

use NF\NeoFrag\Addons\Module;

class Discord extends Module
{
	/*
	 * Les permissions Discord du lien d'invitation : celles dont le bot a besoin, et pas
	 * « Administrateur ». C'est `PERMISSIONS_DU_BOT` de `bot/src/discord.ts`, additionnée — un test
	 * du bot vérifie que les deux disent la même chose.
	 */
	const PERMISSIONS_DISCORD = '329504648256';

	/** Les droits de la clé d'API que le bot emploie. */
	const DROITS_DU_BOT = ['discord:bot', 'members:read', 'forum:read', 'forum:write', 'events:read'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Discord'),
			'description' => $this->lang('Le bot Discord du site : sa connexion, son état, son journal, et les correspondances entre salons et forums, groupes et rôles.'),
			'icon'        => 'fab fa-discord',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['api'],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'admin'                              => 'index',
				'admin/connexion'                    => '_connexion',
				'admin/cle'                          => '_cle',
				'admin/commande/{url_title}'         => '_commande',
				'admin/salons'                       => '_salons',
				'admin/salons/supprimer/{id}'        => '_salons_supprimer',
				'admin/roles'                        => '_roles',
				'admin/roles/supprimer/{id}'         => '_roles_supprimer',
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Discord'),
						'icon'   => 'fab fa-discord',
						'access' => [
							'manage' => ['title' => $this->lang('Régler le bot Discord'), 'icon' => 'fas fa-robot', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Les phrases du journal du bot (`bot/src`). Le bot envoie le modèle d'une ligne et ses valeurs ;
	 * l'administration la montre traduite (`traduire_journal()`). Cette liste n'est lue par personne
	 * à l'exécution : elle fait entrer les modèles dans les traductions du module, que `check-langs`
	 * et `fill-langs` tiennent dans les six langues. Un test du bot vérifie que chacun de ses modèles
	 * y figure — en ajouter un au bot, c'est l'ajouter ici.
	 *
	 * @return list<mixed>
	 */
	public function textes_du_bot(): array
	{
		return [
			$this->lang('Aucune clé de bot enregistrée : renseignez-la dans l’administration du site (Discord → Connexion).'),
			$this->lang('Aucun serveur Discord choisi : renseignez son identifiant dans l’administration du site (Discord → Connexion).'),
			$this->lang('Discord refuse la clé du bot : elle a peut-être été régénérée. Collez la nouvelle dans l’administration du site (Discord → Connexion).'),
			$this->lang('Discord injoignable : %s'),
			$this->lang('« Server Members Intent » n’est pas activé dans le portail des développeurs Discord : les rôles et les pseudos ne seront pas synchronisés.'),
			$this->lang('« Message Content Intent » n’est pas activé dans le portail des développeurs Discord : le texte des messages ne sera pas recopié sur le forum.'),
			$this->lang('Discord : %s'),
			$this->lang('Connexion à Discord perdue : reconnexion…'),
			$this->lang('Connexion à Discord rétablie.'),
			$this->lang('Discord n’a pas accepté la connexion dans la minute.'),
			$this->lang('Discord refuse la clé du bot : collez la bonne dans l’administration du site (Discord → Connexion).'),
			$this->lang('Connexion à Discord impossible : %s'),
			$this->lang('Connecté à Discord : %s.'),
			$this->lang('Le bot n’est pas sur le serveur %s. Invitez-le avec ce lien : %s'),
			$this->lang('Fil d’événements du site illisible : %s'),
			$this->lang('Serveur « %s » rejoint : %d fonctionnalité(s) à démarrer.'),
			$this->lang('Fonctionnalité « %s » (%s) : %s'),
			$this->lang('Forum : %s'),
			$this->lang('Forum : le sujet « %s » est publié sur Discord.'),
			$this->lang('Forum : le sujet n° %d est supprimé sur le site, son fil « %s » aussi sur Discord.'),
			$this->lang('Forum : le message d’ouverture du fil « %s » a été supprimé sur Discord ; le sujet reste sur le site.'),
			$this->lang('Forum : le fil « %s » a été supprimé sur Discord ; le sujet n° %d reste sur le site (à supprimer à la main au besoin).'),
			$this->lang('Forum : le message d’ouverture du fil « %s » est introuvable, le fil n’est pas publié.'),
			$this->lang('Forum : le fil « %s » est publié sur le site à la demande de %s (%d réponse(s)).'),
			$this->lang('Forum : le fil « %s » est publié sur le site (sujet n° %d).'),
			$this->lang('Forum : une réponse de %s n’est pas recopiée, le sujet n° %d est verrouillé sur le site.'),
			$this->lang('Forum : Discord → site en attente — activez « Message Content Intent » dans le portail des développeurs Discord, puis redémarrez le bot.'),
			$this->lang('Forum : le salon relié au forum n° %d n’existe plus sur le serveur.'),
			$this->lang('Forum : le salon « %s » n’est pas un salon Forum ; seul un salon Forum peut être relié à un forum du site.'),
			$this->lang('Forum : dans le salon « %s », il manque au bot les permissions : %s.'),
			$this->lang('Forum : %d salon(s) relié(s) à un forum du site.'),
			$this->lang('Rôles de %s (arrivée) : %s'),
			$this->lang('Rôles et pseudos en attente : activez « Server Members Intent » dans le portail des développeurs Discord, puis redémarrez le bot.'),
			$this->lang('Rôles et pseudos : %d membre(s) lié(s) présent(s) sur le serveur — %d rôle(s) donné(s), %d retiré(s), %d pseudo(s) changé(s).'),
			$this->lang('%d pseudo(s) hors de portée : ces membres ont un rôle égal ou supérieur à celui du bot (ou possèdent le serveur).'),
			$this->lang('Rôles et pseudo de %s : %d rôle(s) donné(s), %d retiré(s), pseudo changé.'),
			$this->lang('Rôles et pseudo de %s : %d rôle(s) donné(s), %d retiré(s).'),
			$this->lang('Le bot n’a pas la permission « Gérer les rôles » sur le serveur : les rôles ne sont pas synchronisés.'),
			$this->lang('Le rôle relié au groupe « %s » n’existe plus sur le serveur : défaites ou refaites la correspondance dans l’administration.'),
			$this->lang('Le rôle « %s » est tenu par une intégration : Discord interdit de le donner.'),
			$this->lang('Le rôle « %s » est au-dessus du rôle du bot : dans les réglages du serveur (Rôles), placez le rôle du bot plus haut.'),
			$this->lang('Rôles et pseudos : %d échec(s) — %s. Le rôle du bot doit être placé au-dessus des rôles qu’il donne et des membres qu’il renomme.'),
			$this->lang('Erreur inattendue : %s'),
			$this->lang('Bot NeoFrag Reborn %s démarré — site %s'),
			$this->lang('%d ligne(s) du journal perdue(s) : le site était injoignable trop longtemps.'),
			$this->lang('Erreur inattendue du superviseur : %s'),
			$this->lang('Bot arrêté.'),
			$this->lang('Site de nouveau joignable, après %d signe(s) de vie manqué(s).'),
			$this->lang('Configuration relue depuis l’administration.'),
			$this->lang('La connexion à Discord a changé : reconnexion.'),
			$this->lang('Redémarrage demandé depuis l’administration.'),
			$this->lang('Mis en pause depuis l’administration : déconnexion de Discord.'),
			$this->lang('Fermeture de la connexion à Discord : %s'),
			$this->lang('Le site refuse la clé d’accès du bot (NF_API_KEY) : créez-en une nouvelle dans l’administration (Discord → Clé d’accès du bot).'),
			$this->lang('La clé d’accès du bot n’a pas le droit « discord:bot » : créez-la depuis l’administration (Discord → Clé d’accès du bot).'),
			$this->lang('Le module Discord n’est pas installé sur le site.'),
			$this->lang('Site injoignable : %s'),
		];
	}

	/**
	 * Une ligne du journal du bot, traduite : seulement un modèle que le module connaît (il est dans
	 * ses traductions françaises), et rempli d'autant de valeurs qu'il a d'emplacements — sinon NULL,
	 * et l'administration montre la phrase française que le bot a envoyée avec.
	 *
	 * @param list<string> $valeurs
	 */
	public function traduire_journal(string $modele, array $valeurs): ?string
	{
		static $connus = NULL;

		$connus ??= (array) include __DIR__.'/langs/fr.php';

		if ($modele === '' || !isset($connus[hash('crc32b', $modele)]) || preg_match_all('/(?<!%)%[sd]/', $modele) !== count($valeurs))
		{
			return NULL;
		}

		return (string) $this->lang($modele, ...array_map('strval', $valeurs));
	}
}
