# NeoFrag Reborn — les addons à la carte

Les modules, widgets et thèmes que [NeoFrag Reborn](https://github.com/NeoFragReborn/neofrag)
n'installe pas d'office : une installation les ajoute après coup, depuis son administration
(*Système → Thèmes & addons → Marketplace*), ou en envoyant leur archive par *Ajouter*. Le paquet
d'installation du CMS les contient déjà tous ; le marketplace sert à les mettre à jour, et à les
reprendre après une suppression.

Chaque version de ce dépôt joint à sa [page de version](https://github.com/NeoFragReborn/extensions/releases)
les archives de tous les addons distribuables et le catalogue du marketplace (`catalog.json`), avec
leurs empreintes SHA-256.

## Les addons

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

<!-- addons:fin -->

## Développer un addon

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
[un thème](https://github.com/NeoFragReborn/neofrag/blob/main/docs/guide/create-a-theme.md). Le chemin
d'une contribution : [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md).

## Licence

LGPL-3.0 ou ultérieure ([COPYING](COPYING), [COPYING.LESSER](COPYING.LESSER)), sauf le thème Extend,
portage du thème de Chewbaka, sous CC BY-NC-SA 4.0. Qui a écrit chaque addon : [NOTICE](NOTICE).
