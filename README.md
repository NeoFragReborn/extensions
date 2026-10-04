<div align="center">

<a href="https://neofrag-reborn.xyz"><img src="https://neofrag-reborn.xyz/themes/vitrine/images/partage/fr.png" alt="NeoFrag Reborn — le CMS libre des communautés" width="720"></a>

# NeoFrag Reborn — les addons à la carte

**Les modules, widgets et thèmes à ajouter à un site NeoFrag Reborn, depuis son marketplace.**

[![Version](https://img.shields.io/github/v/release/NeoFragReborn/extensions?label=version&color=2dd4bf)](https://github.com/NeoFragReborn/extensions/releases/latest)
[![CI](https://img.shields.io/github/actions/workflow/status/NeoFragReborn/extensions/ci.yml?branch=main&label=CI&logo=githubactions&logoColor=white)](https://github.com/NeoFragReborn/extensions/actions/workflows/ci.yml)
[![PHP 8.2 à 8.5](https://img.shields.io/badge/PHP-8.2%20%C3%A0%208.5-777bb4.svg?logo=php&logoColor=white)](https://github.com/NeoFragReborn/neofrag/blob/main/docs/guide/installation.md#prérequis)
[![Licence LGPL-3.0-or-later](https://img.shields.io/badge/licence-LGPL--3.0--or--later-blue.svg)](COPYING.LESSER)
[![Discord](https://img.shields.io/badge/Discord-rejoindre-5865F2.svg?logo=discord&logoColor=white)](https://discord.gg/UmBRbwxtch)

[Le CMS](https://github.com/NeoFragReborn/neofrag) · [Site du projet](https://neofrag-reborn.xyz) ·
[Démonstration](https://demo.neofrag-reborn.xyz) · [Versions](https://github.com/NeoFragReborn/extensions/releases) ·
[Discord](https://discord.gg/UmBRbwxtch) · [English](#-in-english)

</div>

Les modules, widgets et thèmes que [NeoFrag Reborn](https://github.com/NeoFragReborn/neofrag)
n'installe pas d'office : une installation les ajoute après coup, depuis son administration
(*Système → Thèmes & addons → Marketplace*), ou en envoyant leur archive par *Ajouter*. Le paquet
d'installation du CMS les contient déjà tous ; le marketplace sert à les mettre à jour, et à les
reprendre après une suppression.

## 📦 Installer un addon

1. 🧩 **Depuis le marketplace** — dans l'administration du site, *Système → Thèmes & addons →
   Marketplace* : choisir l'addon et l'installer ; l'archive est vérifiée par son empreinte SHA-256.
2. 📁 **À la main** — télécharger son archive sur la
   [page des versions](https://github.com/NeoFragReborn/extensions/releases), puis l'envoyer par *Ajouter*.

Chaque version de ce dépôt joint à sa [page de version](https://github.com/NeoFragReborn/extensions/releases)
les archives de tous les addons distribuables et le catalogue du marketplace (`catalog.json`), avec
leurs empreintes SHA-256.

Le marketplace propose aussi les addons que les profils d'installation posent d'office — le forum, les
actualités, les événements, la galerie… Leur code vit avec le cœur, dans le dépôt
[neofrag](https://github.com/NeoFragReborn/neofrag), pour qu'un clone de celui-ci installe chaque profil
sans rien d'autre ; ils sont listés à la fin du tableau ci-dessous, avec un lien vers leur code. Un site
installé « Cœur seul » les ajoute depuis le marketplace.

## 📋 Les addons

Les tableaux suivent le catalogue du marketplace livré avec cette version.

<!-- addons:début -->
### Modules (19)

| Addon | Ce qu'il fait | Version | Auteur |
|---|---|---|---|
| [`ads`](modules/ads) | Régie publicitaire — Bannières et blocs HTML par emplacement, masqués pour les membres sans-pub / VIP. | 1.0 | NeoFrag Reborn |
| [`articles`](modules/articles) | Blog — Articles longs avec catégories, tags, sommaire automatique et temps de lecture. | 1.0 | NeoFrag Reborn |
| [`bugtracker`](modules/bugtracker) | Bugtracker — Suivi de bugs et tickets internes avec types, priorités et statuts configurables. | 1.0 | NeoFrag Reborn |
| [`classifieds`](modules/classifieds) | Petites annonces — Annonces entre membres (offres / demandes) classées par catégorie, avec contact vendeur. | 1.0 | NeoFrag Reborn |
| [`discord`](modules/discord) | Discord — Le bot Discord du site : sa connexion, son état, son journal, et les correspondances entre salons et forums, groupes et rôles. | 1.0 | NeoFrag Reborn |
| [`downloads`](modules/downloads) | Téléchargements — Bibliothèque de fichiers à télécharger avec catégories, version et compteur. | 1.0 | NeoFrag Reborn |
| [`emojis`](modules/emojis) | Emojis — Emojis personnalisés (`:nom:`) uploadés en admin, rendus dans tout le contenu. | 1.0 | NeoFrag Reborn |
| [`feeds`](modules/feeds) | Flux RSS — Flux RSS 2.0 des actualités et articles. | 1.0 | NeoFrag Reborn |
| [`glossary`](modules/glossary) | Dictionnaire — Lexique des termes de la communauté, rangé par lettre, avec ses synonymes. | 1.0 | NeoFrag Reborn |
| [`guestbook`](modules/guestbook) | Livre d'or — Livre d'or avec modération admin et rate-limit anti-spam. | 1.0 | NeoFrag Reborn |
| [`links`](modules/links) | Annuaire de liens — Annuaire de liens externes catégorisés avec redirection trackée et compteur de clics. | 1.0 | NeoFrag Reborn |
| [`payments`](modules/payments) | Paiements — Recharge de points et packs VIP via Stripe (argent réel). | 1.0 | NeoFrag Reborn |
| [`places`](modules/places) | Carte des lieux — Des lieux situés sur une carte OpenStreetMap, avec leur adresse et leur description. | 1.0 | NeoFrag Reborn |
| [`quotes`](modules/quotes) | Citations — Recueil de citations classées, avec leur auteur et leur source. | 1.0 | NeoFrag Reborn |
| [`recipes`](modules/recipes) | Recettes — Recueil de recettes classées, avec leurs ingrédients, leurs étapes et leurs durées. | 1.0 | NeoFrag Reborn |
| [`sandbox`](modules/sandbox) | Bac à sable — Un endroit pour essayer la mise en forme et voir ce que le site en fait, sans rien publier. | 1.0 | NeoFrag Reborn |
| [`shop`](modules/shop) | Boutique — Boutique de biens virtuels (grades, cosmétiques, VIP…) payables en points, et merch. | 1.0 | NeoFrag Reborn |
| [`surveys`](modules/surveys) | Sondages — Sondages publics à choix unique ou multiple avec résultats configurables. | 1.0 | NeoFrag Reborn |
| [`webradio`](modules/webradio) | Webradio — Lecteur d'un flux de webradio et grille des émissions de la semaine. | 1.0 | NeoFrag Reborn |

### Widgets (8)

| Addon | Ce qu'il fait | Version | Auteur |
|---|---|---|---|
| [`ads`](widgets/ads) | Publicité — Affiche une annonce de la régie pour un emplacement (masquée pour les membres sans-pub / VIP). | 1.0 | NeoFrag Reborn |
| [`articles`](widgets/articles) | Blog — Les billets du Blog : les derniers, les plus lus, celui à la une, les catégories, les tags et les archives. | 1.1 | NeoFrag Reborn |
| [`downloads`](widgets/downloads) | Téléchargements — Liste des derniers fichiers à télécharger. | 1.0 | NeoFrag Reborn |
| [`guestbook`](widgets/guestbook) | Livre d'or — Derniers messages du livre d'or. | 1.0 | NeoFrag Reborn |
| [`links`](widgets/links) | Liens — Liens populaires de l'annuaire. | 1.0 | NeoFrag Reborn |
| [`rss`](widgets/rss) | Lecteur de flux — Les derniers articles d'un flux RSS ou Atom extérieur, mis en cache. | 1.0 | NeoFrag Reborn |
| [`seasonal`](widgets/seasonal) | Effet saisonnier — Neige, confettis ou feuilles sur tout l'écran, pendant une plage de dates choisie. | 1.0 | NeoFrag Reborn |
| [`surveys`](widgets/surveys) | Sondages — Sondage en cours sur le site. | 1.0 | NeoFrag Reborn |

### Thèmes (4)

| Addon | Ce qu'il fait | Version | Auteur |
|---|---|---|---|
| [`blockcraft`](themes/blockcraft) | Blockcraft — Thème gaming inspiré des univers cubiques. Mode jour « biome » et mode nuit « grotte », accent vert herbe, entièrement personnalisable. | 1.0.0 | NeoFrag Reborn |
| [`extend`](themes/extend) | Extend — Thème « Extend » : navy et bleu acier, titres condensés Economica, bascule nuit/jour, mise en page riche (navigation, bannière, multi-zones). Entièrement personnalisable. | 1.0.0 | Chewbaka — portage NeoFrag Reborn |
| [`forge`](themes/forge) | Forge — Thème gaming « fonte en fusion » : rouge lave sur charbon, nuit par défaut et mode jour, lueur de braise, titres Rajdhani. Entièrement personnalisable. | 1.0.0 | NeoFrag Reborn |
| [`granite`](themes/granite) | Granite — Thème gaming épuré « roche & sommet », accent teal sur ardoise. Mode jour et mode nuit, titres condensés, entièrement personnalisable. | 1.0.0 | NeoFrag Reborn |

### Posés d'office par les profils, dans le dépôt neofrag (32)

Le marketplace les propose aussi, et leurs archives sont jointes aux versions de ce dépôt ; leur code
vit avec le cœur, dans [neofrag](https://github.com/NeoFragReborn/neofrag).

| Addon | Type | Ce qu'il fait | Version | Auteur |
|---|---|---|---|---|
| [`api`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/api) | module | API — L’API REST du site : des clés d’accès pour les programmes (le bot Discord, une intégration), des adresses versionnées qui rendent du JSON. | 1.0 | NeoFrag Reborn |
| [`awards`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/awards) | module | Palmarès — Récompenses (palmarès) attribuables aux membres ou équipes — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`calendar`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/calendar) | module | Calendrier — Calendrier d'événements avec export iCal RFC 5545. | 1.0 | NeoFrag Reborn |
| [`donations`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/donations) | module | Dons — Système de campagnes de dons avec objectif, barre de progression, liste de donateurs et bouton PayPal. | 1.0 | HiddenBlob (Donation v3), d’après majiid — portage NeoFrag Reborn |
| [`events`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/events) | module | Événements gaming — Événements et tournois — planning, participants, types — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`faq`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/faq) | module | FAQ — Foire aux questions catégorisée affichée en accordéon Bootstrap. | 1.0 | NeoFrag Reborn |
| [`forum`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/forum) | module | Forum — Forum communautaire avec catégories, sous-forums, sujets épinglés et permissions. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`gallery`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/gallery) | module | Galeries — Galerie photos avec albums, thumbnails et descriptions. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`games`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/games) | module | Jeux / Cartes — Catalogue de jeux pratiqués — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`gamification`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/gamification) | module | Gamification — Karma, points et VIP. Réputation et monnaie virtuelle dérivées de l'activité (barème réglable). | 1.0 | NeoFrag Reborn |
| [`news`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/news) | module | Actualités — Actualités du site avec catégories et commentaires. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`newsletter`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/newsletter) | module | Newsletter — Inscription à la newsletter (double opt-in) et envoi de campagnes. | 1.0 | NeoFrag Reborn |
| [`partners`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/partners) | module | Partenaires — Partenaires et sponsors — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`recruits`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/recruits) | module | Recrutements — Recrutement de joueurs avec candidatures et système de votes — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`teams`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/teams) | module | Équipes — Équipes et clans — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`wiki`](https://github.com/NeoFragReborn/neofrag/tree/main/modules/wiki) | module | Wiki — Pages collaboratives avec historique de révisions automatique. | 1.0 | NeoFrag Reborn |
| [`about`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/about) | widget | À propos — Bloc de présentation libre éditable en HTML. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`awards`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/awards) | widget | Palmarès — Affiche les dernières récompenses attribuées — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`calendar`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/calendar) | widget | Calendrier — Prochains événements du calendrier. | 1.0 | NeoFrag Reborn |
| [`donations`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/donations) | widget | Campagne de dons — Affiche la progression d'une campagne de dons : barre de progression, montant collecté, top donateurs et bouton "Faire un don". | 1.0 | HiddenBlob (Donation v3), d’après majiid — portage NeoFrag Reborn |
| [`events`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/events) | widget | Événements — Calendrier ou liste des événements à venir — module gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`forum`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/forum) | widget | Forum — Derniers sujets ou messages du forum. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`gallery`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/gallery) | widget | Galeries — Aperçu de la galerie photos avec dernières images. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`gameserver`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/gameserver) | widget | Serveur de jeu — Affiche le statut, le nombre de joueurs, la carte et un bouton "Rejoindre" pour un serveur de jeu (Minecraft Java, Minecraft Bedrock, Source / GoldSource — CS2, GMod, ARMA, Rust, etc.). | 1.0 | NeoFrag Reborn |
| [`news`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/news) | widget | Actualités — Liste des dernières actualités publiées. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`newsletter`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/newsletter) | widget | Newsletter — Formulaire d'inscription à la newsletter. | 1.0 | NeoFrag Reborn |
| [`partners`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/partners) | widget | Partenaires — Partenaires et sponsors avec logos — widget gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`recruits`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/recruits) | widget | Recrutement — Postes actuellement ouverts au recrutement — widget gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`steam`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/steam) | widget | Groupe Steam — Affiche le nombre de membres, la présence en ligne et l'activité d'un groupe Steam. | 2.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`teams`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/teams) | widget | Équipes — Équipes du clan avec leurs membres — widget gaming. | 1.0 | Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com> |
| [`teamspeak`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/teamspeak) | widget | Serveur TeamSpeak 3 — Affiche les channels et clients connectés à un serveur TeamSpeak 3, avec un bouton "Se connecter". | 3.0 | NeoFrag Reborn |
| [`twitch`](https://github.com/NeoFragReborn/neofrag/tree/main/widgets/twitch) | widget | Statut live — Statut en direct de plusieurs chaînes Twitch / YouTube (jeu, viewers, titre, miniature) avec lecteur intégré. | 3.0 | NeoFrag Reborn |

<!-- addons:fin -->

## 🔧 Développer un addon

Un addon de ce dépôt ne tourne que posé dans un arbre de [neofrag](https://github.com/NeoFragReborn/neofrag),
à son chemin (`modules/<nom>`, `widgets/<nom>`, `themes/<nom>`) : c'est là que vivent le cœur, les outils
et les tests. Son outil `assembler` les y pose tous, depuis ce clone :

```bash
git clone https://github.com/NeoFragReborn/neofrag.git
git clone https://github.com/NeoFragReborn/extensions.git
cd neofrag && composer install
php tools/assembler.php --extensions=../extensions
```

Les guides : [créer un module](https://github.com/NeoFragReborn/neofrag/blob/main/docs/guide/create-a-module.md),
[un widget](https://github.com/NeoFragReborn/neofrag/blob/main/docs/guide/create-a-widget.md),
[un thème](https://github.com/NeoFragReborn/neofrag/blob/main/docs/guide/create-a-theme.md).

## 🤝 Contribuer

- 🔧 **du code** — le [guide du contributeur](.github/CONTRIBUTING.md) : éprouver un changement, les
  versions, les conventions (celles du CMS) ;
- 🐛 **un bug dans un addon à la carte** — une [issue](https://github.com/NeoFragReborn/extensions/issues),
  avec la version de l'addon et celle du site ;
- 💡 **une question, une idée** — le [serveur Discord](https://discord.gg/UmBRbwxtch) ou les
  [Discussions](https://github.com/NeoFragReborn/neofrag/discussions) du CMS ;
- 🔒 **une faille de sécurité** — jamais d'issue publique : la
  [politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).

## 🙏 Crédits

NeoFrag a été créé par **Michaël BILCOT** (FoxLey) et **Jérémy VALENTIN** (eResnova) ; leurs addons
gardent leur signature, dans la colonne *Auteur* des tableaux. Le thème Extend est le portage du thème de
**Chewbaka**, et les Dons celui du module de **HiddenBlob**, d'après **majiid**. Merci à eux, et à ceux
dont les idées ont inspiré d'autres addons.

## 📜 Licence

LGPL-3.0 ou ultérieure ([COPYING](COPYING), [COPYING.LESSER](COPYING.LESSER)), sauf le thème Extend,
portage du thème de Chewbaka, sous CC BY-NC-SA 4.0. Qui a écrit chaque addon : [NOTICE](NOTICE).

## 🌍 In English

This repository holds the optional add-ons — modules, widgets and themes — of
[NeoFrag Reborn](https://github.com/NeoFragReborn/neofrag), a free PHP CMS for communities. A site adds
them from the marketplace in its administration, which checks each archive's SHA-256 fingerprint, or by
uploading an archive by hand. Each [release](https://github.com/NeoFragReborn/extensions/releases)
attaches every add-on archive and the marketplace catalogue; the tables above list them all. The code
and the documentation are in French. Join the project on [Discord](https://discord.gg/UmBRbwxtch).
