-- Le nom d'une rubrique de petites annonces se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le nom d'une rubrique de petites annonces n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions, comme les téléchargements, les liens, la FAQ et les sondages ; les pages servent la meilleure
-- (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : l’ordre de tri et l’icône.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_classifieds_categories_lang` (
  `category_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_classifieds_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_classifieds_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_classifieds_categories_lang` (`category_id`, `lang`, `title`)
SELECT `t`.`id`, `l`.`name`, `t`.`title`
FROM `nf_classifieds_categories` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_classifieds_categories` DROP COLUMN `title`;
