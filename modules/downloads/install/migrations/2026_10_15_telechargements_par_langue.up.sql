-- Les téléchargements se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- Le titre et la description d'un fichier, et ceux de ses rubriques, vivaient dans `nf_downloads` et
-- `nf_downloads_categories` : un site en six langues les montrait dans une seule — relevé sur la démonstration en
-- anglais, qui affichait « Config CS2 recommandée » et « Pack de fonds d'écran ». Ils passent dans deux tables de
-- traductions, comme les actualités, les événements ou le palmarès ; les pages servent la meilleure
-- (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : l'adresse du fichier, sa taille, son type, sa version, le compteur de
-- téléchargements, la date, la rubrique et son ordre de tri.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_downloads_lang` (
  `download_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`download_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_downloads_lang_ibfk_1` FOREIGN KEY (`download_id`) REFERENCES `nf_downloads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_downloads_categories_lang` (
  `category_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_downloads_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_downloads_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_downloads_lang` (`download_id`, `lang`, `title`, `description`)
SELECT `d`.`id`, `l`.`name`, `d`.`title`, `d`.`description`
FROM `nf_downloads` `d`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

INSERT IGNORE INTO `nf_downloads_categories_lang` (`category_id`, `lang`, `title`, `description`)
SELECT `c`.`id`, `l`.`name`, `c`.`title`, `c`.`description`
FROM `nf_downloads_categories` `c`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_downloads` DROP COLUMN `title`, DROP COLUMN `description`;
ALTER TABLE `nf_downloads_categories` DROP COLUMN `title`, DROP COLUMN `description`;
