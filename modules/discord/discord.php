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
	const PERMISSIONS_DISCORD = '329504648272';

	/** Les droits de la clé d'API que le bot emploie. */
	const DROITS_DU_BOT = ['discord:bot', 'members:read', 'forum:read', 'forum:write', 'events:read', 'bugtracker:read', 'bugtracker:write'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Discord'),
			'description' => $this->lang('Le bot Discord du site : sa connexion, son état, son journal, et les correspondances entre salons et forums, groupes et rôles.'),
			'icon'        => 'fab fa-discord',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => ['api'],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				// Relier son compte Discord depuis Discord (`/forum account link`) : le lien à usage unique.
				'lier/{url_title}'                   => '_lier',
				'lier/{url_title}/confirmer/{id}'    => '_confirmer',
				'admin'                              => 'index',
				'admin/connexion'                    => '_connexion',
				'admin/cle'                          => '_cle',
				'admin/commande/{url_title}'         => '_commande',
				'admin/salons'                       => '_salons',
				'admin/salons/supprimer/{id}'        => '_salons_supprimer',
				'admin/etiquettes/{id}'              => '_etiquettes',
				'admin/roles'                        => '_roles',
				'admin/roles/supprimer/{id}'         => '_roles_supprimer',
				'admin/roles-temporaires'            => '_roles_temporaires',
				'admin/roles-temporaires/retirer/{id}' => '_roles_temporaires_retirer',
				'admin/fonctionnalites'              => '_fonctionnalites',
				'admin/fonctionnalite/{url_title}'   => '_fonctionnalite',
				'admin/fonctionnalite/{url_title}/basculer' => '_basculer',
				'admin/mise-en-place'                => '_mise_en_place',
				'admin/mise-en-place/apercu'         => '_mise_en_place_apercu',
				'admin/mise-en-place/appliquer'      => '_mise_en_place_appliquer',
				'admin/mise-en-place/annuler'        => '_mise_en_place_annuler',
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
			$this->lang('Fonctionnalité « %s » éteinte depuis l’administration.'),
			$this->lang('Fonctionnalité « %s » allumée depuis l’administration.'),
			$this->lang('Commande « %s » ignorée : le bot n’est pas encore sur le serveur.'),
			$this->lang('Mise en place du serveur : %s'),
			$this->lang('Commande « %s » inconnue de cette version du bot.'),
			$this->lang('Fil d’événements du site illisible : %s'),
			$this->lang('Serveur « %s » rejoint : %d fonctionnalité(s) à démarrer.'),
			$this->lang('Commandes Discord à jour : %d commande(s).'),
			$this->lang('Commandes Discord non enregistrées : %s'),
			$this->lang('Fonctionnalité « %s » (%s) : %s'),
			$this->lang('Bugtracker'),
			$this->lang('Chaque ticket du Bugtracker devient un fil d’un salon Forum : son type et son statut en sont les étiquettes, les commentaires passent dans les deux sens, et /bug ou /idee ouvrent un ticket depuis Discord.'),
			$this->lang('Salon Forum des tickets'),
			$this->lang('Chaque ticket y devient un fil. Le bot y crée les étiquettes des types et des statuts.'),
			$this->lang('Proposer /bug et /idee pour ouvrir un ticket depuis Discord'),
			$this->lang('Donner aussi un fil aux tickets encore ouverts'),
			$this->lang('Au démarrage du bot et quand le salon change : les tickets ouverts qui n’ont pas encore de fil en reçoivent un.'),
			$this->lang('Bugtracker : le ticket n° %d est publié sur Discord.'),
			$this->lang('Bugtracker : le ticket n° %d est supprimé sur le site, son fil aussi.'),
			$this->lang('Bugtracker : le fil « %s » devient le ticket n° %d.'),
			$this->lang('Bugtracker : %d ticket(s) ouvert(s) ont reçu leur fil.'),
			$this->lang('Bugtracker : choisissez le salon Forum des tickets dans l’administration (Discord → Fonctionnalités → Bugtracker).'),
			$this->lang('Bugtracker : le salon choisi n’existe plus ou n’est pas un salon Forum.'),
			$this->lang('Bugtracker : le salon « %s » est déjà relié à un forum du site ; choisissez-en un autre pour les tickets.'),
			$this->lang('Bugtracker : étiquettes créées dans le salon : %s.'),
			$this->lang('Bugtracker : %s'),
			$this->lang('Bugtracker : la clé d’accès du bot n’a pas les droits du Bugtracker ; créez-en une nouvelle dans l’administration (Discord → Clé d’accès du bot).'),
			$this->lang('Bugtracker : le Bugtracker n’est pas installé sur le site.'),
			$this->lang('Compte et apparence'),
			$this->lang('La commande /forum : relier ou délier son compte Discord depuis Discord, et choisir comment on apparaît sur le forum sans compte relié.'),
			$this->lang('Proposer /forum visibility : choisir son apparence sur le forum sans compte relié'),
			$this->lang('Compte et apparence : %s'),
			$this->lang('Forum et salons Forum'),
			$this->lang('Relie chaque forum du site à un salon Forum de Discord : sujets, réponses, modifications et suppressions passent d’un côté à l’autre.'),
			$this->lang('Sous chaque sujet recopié sur Discord, un lien vers le site'),
			$this->lang('Au démarrage, rattraper ce qui s’est écrit sur Discord pendant que le bot était éteint'),
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
			$this->lang('Forum : rattrapage terminé — %d sujet(s) et %d réponse(s) recopiés sur le site.'),
			$this->lang('Forum : le salon relié au forum n° %d n’existe plus sur le serveur.'),
			$this->lang('Forum : le salon « %s » n’est pas un salon Forum ; seul un salon Forum peut être relié à un forum du site.'),
			$this->lang('Forum : dans le salon « %s », il manque au bot les permissions : %s.'),
			$this->lang('Forum : %d salon(s) relié(s) à un forum du site.'),
			$this->lang('Rôles et pseudos'),
			$this->lang('Donne aux membres qui ont lié leur compte Discord les rôles reliés à leurs groupes, et leur pseudo du site s’ils le veulent.'),
			$this->lang('Donner aux membres liés leur pseudo du site sur le serveur'),
			$this->lang('Minutes entre deux passages sur tous les membres'),
			$this->lang('Un changement de groupe est appliqué tout de suite ; ce passage rattrape le reste (un pseudo changé, un compte lié).'),
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
			$this->lang('Rôles temporaires'),
			$this->lang('La commande /role : donner un rôle pour un temps limité — une sanction, un accès d’essai, un rôle d’événement. Le bot le retire à la fin, même après un redémarrage.'),
			$this->lang('Durée maximale d’un rôle temporaire, en jours'),
			$this->lang('Redonner le rôle à un membre qui quitte puis rejoint le serveur avant la fin'),
			$this->lang('Rôles temporaires : %s'),
			$this->lang('Rôles temporaires : %s retire le rôle « %s » à %s.'),
			$this->lang('Rôles temporaires : %s donne le rôle « %s » à %s.'),
			$this->lang('Rôles temporaires : le rôle « %s » de %s est arrivé à échéance.'),
			$this->lang('Rôles temporaires : impossible de retirer le rôle « %s » à %s (%s).'),
			$this->lang('Rôles temporaires : le rôle « %s » est redonné à %s, revenu sur le serveur avant la fin.'),
			$this->lang('Erreur inattendue : %s'),
			$this->lang('Bot NeoFrag Reborn %s démarré — site %s'),
			$this->lang('%d ligne(s) du journal perdue(s) : le site était injoignable trop longtemps.'),
			$this->lang('Mise en place du serveur : %d salon(s) et %d rôle(s) créés, %d repris.'),
			$this->lang('Mise en place annulée : %d salon(s) et %d rôle(s) supprimés.'),
			$this->lang('Erreur inattendue du superviseur : %s'),
			$this->lang('Bot arrêté.'),
			$this->lang('Site de nouveau joignable, après %d signe(s) de vie manqué(s).'),
			$this->lang('Configuration relue depuis l’administration.'),
			$this->lang('La connexion à Discord a changé : reconnexion.'),
			$this->lang('Redémarrage demandé depuis l’administration.'),
			$this->lang('Mis en pause depuis l’administration : déconnexion de Discord.'),
			$this->lang('Commande « %s » ignorée : le bot n’est pas connecté à Discord.'),
			$this->lang('Resynchronisation demandée depuis l’administration.'),
			$this->lang('Fermeture de la connexion à Discord : %s'),
			$this->lang('Le site refuse la clé d’accès du bot (NF_API_KEY) : créez-en une nouvelle dans l’administration (Discord → Clé d’accès du bot).'),
			$this->lang('La clé d’accès du bot n’a pas le droit « discord:bot » : créez-la depuis l’administration (Discord → Clé d’accès du bot).'),
			$this->lang('Le module Discord n’est pas installé sur le site.'),
			$this->lang('Site injoignable : %s'),
			$this->lang('Une erreur est survenue ; elle est notée dans le journal du bot.'),
			$this->lang('Le site ne répond pas pour le moment ; réessaie dans un instant.'),
			$this->lang('Ton compte du site et ton apparence sur son forum'),
			$this->lang('Ton compte du site'),
			$this->lang('Relier ton compte Discord à ton compte du site'),
			$this->lang('Délier ton compte Discord de ton compte du site'),
			$this->lang('Comment tu apparais sur le forum du site si ton compte n’est pas relié'),
			$this->lang('Paraître sous ton pseudo Discord'),
			$this->lang('Paraître sous un nom anonyme'),
			$this->lang('Paraître sous le pseudo de ton choix (changeable tous les 7 jours)'),
			$this->lang('Le pseudo choisi, de 2 à 32 caractères'),
			$this->lang('Voir comment tu apparais sur le forum du site'),
			$this->lang('Pour relier ton compte Discord à ton compte du site, ouvre ce lien — valable %d minutes, une seule fois.'),
			$this->lang('Relier mon compte'),
			$this->lang('Ton compte Discord est déjà relié au compte « %s » du site.'),
			$this->lang('Ton compte Discord n’est plus relié au compte « %s ».'),
			$this->lang('Ton compte Discord n’est relié à aucun compte du site.'),
			$this->lang('Impossible : c’est le seul moyen de te connecter au compte « %s ». Ajoute d’abord un mot de passe à ton compte, sur le site.'),
			$this->lang('Ton compte est relié au compte « %s » du site : tes messages paraissent toujours sous ce compte.'),
			$this->lang('Sur le forum du site, tes messages venus de Discord paraissent sous ton pseudo Discord.'),
			$this->lang('Sur le forum du site, tes messages venus de Discord paraissent sous un nom anonyme : « %s ».'),
			$this->lang('Sur le forum du site, tes messages venus de Discord paraissent sous le pseudo « %s ».'),
			$this->lang('Tous tes messages déjà publiés suivent ce choix.'),
			$this->lang('Ton pseudo choisi ne change qu’une fois tous les 7 jours : prochain changement possible %s.'),
			$this->lang('Le pseudo choisi doit faire de 2 à 32 caractères.'),
			$this->lang('Ce pseudo est celui d’un membre du site : choisis-en un autre.'),
			$this->lang('Signaler un bogue dans le Bugtracker du site'),
			$this->lang('Proposer une idée dans le Bugtracker du site'),
			$this->lang('Signaler un bogue'),
			$this->lang('Proposer une idée'),
			$this->lang('Titre'),
			$this->lang('Description'),
			$this->lang('Ce qui se passe, ce qui était attendu, comment le reproduire.'),
			$this->lang('Ticket n° %d ouvert : %s'),
			$this->lang('La discussion continue dans %s.'),
			$this->lang('Un ticket appartient à un membre du site : relie d’abord ton compte avec /forum account link.'),
			$this->lang('Pour que ce signalement aille dans le Bugtracker du site, relie ton compte avec /forum account link, puis utilise /bug ou /idee.'),
			$this->lang('Ce fil est maintenant le ticket n° %d du Bugtracker : %s'),
			$this->lang('Ticket n° %d · %s · priorité %s'),
			$this->lang('Doublon du ticket n° %d : %s'),
			$this->lang('Bogue'),
			$this->lang('Idée'),
			$this->lang('Question'),
			$this->lang('Autre'),
			$this->lang('Ouvert'),
			$this->lang('En cours'),
			$this->lang('Résolu'),
			$this->lang('Fermé'),
			$this->lang('Ne sera pas fait'),
			$this->lang('Doublon'),
			$this->lang('faible'),
			$this->lang('normale'),
			$this->lang('haute'),
			$this->lang('critique'),
			$this->lang('Donner un rôle pour un temps limité, le retirer, voir ceux en cours'),
			$this->lang('Donner un rôle à un membre pour une durée'),
			$this->lang('Retirer tout de suite un rôle temporaire'),
			$this->lang('Voir les rôles temporaires en cours'),
			$this->lang('Le membre'),
			$this->lang('Le rôle'),
			$this->lang('La durée (avec son unité)'),
			$this->lang('L’unité de la durée'),
			$this->lang('La raison (facultative)'),
			$this->lang('Seulement ce membre (facultatif)'),
			$this->lang('minutes'),
			$this->lang('heures'),
			$this->lang('jours'),
			$this->lang('semaines'),
			$this->lang('Rôle %s donné à %s jusqu’au %s (%s).'),
			$this->lang('Rôle %s retiré à %s.'),
			$this->lang('Ce membre n’a pas ce rôle comme rôle temporaire.'),
			$this->lang('Aucun rôle temporaire en cours.'),
			$this->lang('%s — %s — fin %s'),
			$this->lang('Ce rôle ne peut pas être donné : il est tenu par Discord ou par une intégration.'),
			$this->lang('Ce rôle est relié à un groupe du site : il suit les groupes du membre et ne peut pas être temporaire.'),
			$this->lang('Place le rôle du bot au-dessus de ce rôle dans les réglages du serveur : sans cela, il ne peut ni le donner ni le retirer.'),
			$this->lang('Ce rôle est au niveau de ton rôle le plus haut, ou au-dessus : tu ne peux pas le donner ni le retirer.'),
			$this->lang('Cette durée dépasse le maximum permis sur ce serveur : %d jours.'),
			$this->lang('Un rôle temporaire se donne à un membre, pas à un bot.'),
			$this->lang('Ce membre n’est pas sur le serveur.'),
			$this->lang('Sur le site'),
			$this->lang('Lire la suite sur le site'),
			$this->lang('Ce fil est maintenant aussi sur le site : %s'),
		];
	}

	/**
	 * Le nom d'un salon tel que Discord l'écrit : en minuscules, des tirets à la place des espaces et de
	 * la ponctuation. Le même calcul que `nomDeSalon()` du bot (bot/src/mise-en-place.ts) : l'aperçu de
	 * la mise en place reconnaît ainsi un salon qui existe déjà.
	 */
	public static function nom_de_salon(string $titre): string
	{
		$nom = trim((string) preg_replace(['/[^\p{L}\p{N}_-]+/u', '/-{2,}/'], ['-', '-'], mb_strtolower($titre)), '-');

		return mb_substr($nom, 0, 100) ?: 'forum';
	}

	/** Une couleur de groupe (« danger », « blue », « #ff8800 ») en hexadécimal pour Discord ; NULL si elle n'en a pas. */
	public static function couleur_hexadecimale(string $couleur): ?string
	{
		if (preg_match('/^#[0-9a-f]{6}$/i', $couleur))
		{
			return strtolower($couleur);
		}

		$alias = ['default' => 'primary', 'blue' => 'primary', 'red' => 'danger', 'orange' => 'warning', 'yellow' => 'warning', 'green' => 'success', 'cyan' => 'info', 'teal' => 'info', 'gray' => 'secondary', 'grey' => 'secondary', 'black' => 'dark', 'white' => 'light'];
		$hexa  = ['primary' => '#0d6efd', 'secondary' => '#6c757d', 'success' => '#198754', 'danger' => '#dc3545', 'warning' => '#ffc107', 'info' => '#0dcaf0', 'dark' => '#212529', 'light' => '#f8f9fa'];

		return $hexa[$alias[strtolower($couleur)] ?? strtolower($couleur)] ?? NULL;
	}

	/**
	 * Les textes que le bot poste sur Discord, dans les six langues du produit : il répond à chacun
	 * dans la sienne. Seuls les modèles que le module connaît sont traduits — ceux de
	 * `textes_du_bot()` — ; un texte inconnu garde son français.
	 *
	 * @param list<string> $modeles
	 * @return array<string, array<string, string>> modèle => code de langue => traduction
	 */
	public function traductions_discord(array $modeles): array
	{
		static $langues = NULL;

		if ($langues === NULL)
		{
			$langues = [];

			foreach (['fr', 'en', 'de', 'es', 'it', 'pt'] as $code)
			{
				$langues[$code] = is_file($fichier = __DIR__.'/langs/'.$code.'.php') ? (array) include $fichier : [];
			}
		}

		$traductions = [];

		foreach ($modeles as $modele)
		{
			$cle = hash('crc32b', $modele);

			if (!isset($langues['fr'][$cle]))
			{
				continue;
			}

			foreach ($langues as $code => $table)
			{
				$traductions[$modele][$code] = (string) ($table[$cle] ?? $modele);
			}
		}

		return $traductions;
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
