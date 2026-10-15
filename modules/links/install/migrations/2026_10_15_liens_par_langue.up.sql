-- Les liens utiles se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- Le titre et la description d'un lien, et le titre de ses rubriques, vivaient dans `nf_links` et
-- `nf_links_categories` : un site en six langues les montrait dans une seule. Ils passent dans deux tables de
-- traductions, comme les téléchargements, les actualités ou les événements ; les pages servent la meilleure
-- (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : l'adresse du lien, son compteur de clics, sa rubrique, son ordre de
-- tri et sa date.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_links_lang` (
  `link_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`link_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_links_lang_ibfk_1` FOREIGN KEY (`link_id`) REFERENCES `nf_links` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_links_categories_lang` (
  `category_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_links_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_links_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_links_lang` (`link_id`, `lang`, `title`, `description`)
SELECT `k`.`id`, `l`.`name`, `k`.`title`, `k`.`description`
FROM `nf_links` `k`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

INSERT IGNORE INTO `nf_links_categories_lang` (`category_id`, `lang`, `title`)
SELECT `c`.`id`, `l`.`name`, `c`.`title`
FROM `nf_links_categories` `c`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_links` DROP COLUMN `title`, DROP COLUMN `description`;
ALTER TABLE `nf_links_categories` DROP COLUMN `title`;
