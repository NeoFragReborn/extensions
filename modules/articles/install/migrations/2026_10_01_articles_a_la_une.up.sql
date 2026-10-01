-- Un billet du Blog peut etre mis « a la une » : il ouvre la liste, en grand (2026-10-01).

ALTER TABLE `nf_articles` ADD COLUMN `featured` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `published`;
