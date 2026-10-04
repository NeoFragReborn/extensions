# Contribuer aux addons à la carte

Merci de ton intérêt ! Les règles du projet — le filet de contrôles, les conventions de code, le
français, les déclarations d'un addon (`core`, `presets`, `requires`), les traductions dans six langues —
sont celles du CMS : [CONTRIBUTING de neofrag](https://github.com/NeoFragReborn/neofrag/blob/main/.github/CONTRIBUTING.md).

## Éprouver un changement

Un addon de ce dépôt se teste posé dans un arbre de [neofrag](https://github.com/NeoFragReborn/neofrag),
à son chemin. Modifie-le dans ton clone d'`extensions`, pose-le dans celui de `neofrag` par
`php tools/assembler.php --extensions=../extensions` (à relancer après chaque changement : il remplace
ce qu'il avait posé, et git ne le voit pas), puis joue la batterie du CMS (`php tools/check-all.php`,
`vendor/bin/phpunit --fail-on-skipped`). Un addon qui touche un autre
addon le déclare ou l'annote (`couplage(<addon>): raison`) ; `check-addon-coupling` le vérifie.

## Versions et publication

Ce dépôt reçoit **une version à la fois**, comme le CMS : une PR acceptée est reprise dans la version
suivante, et son auteur crédité dans le CHANGELOG du CMS. Un addon a son propre numéro de version
(`__info()['version']`), que le marketplace compare à celui d'un site pour lui proposer la mise à jour.

## Signaler un bug ou proposer un addon

Utilise les issues de ce dépôt. Pour une faille de sécurité, **pas d'issue publique** : la
[politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).
