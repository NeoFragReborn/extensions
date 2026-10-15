-- Le nom et la description d'un article de la boutique se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le nom et la description d'un article de la boutique n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon
-- la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : le prix, le stock, l’image, l’ordre et l’état.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_shop_items_lang` (
  `item_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`item_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_shop_items_lang_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `nf_shop_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_shop_items_lang` (`item_id`, `lang`, `title`, `description`)
SELECT `t`.`id`, `l`.`name`, `t`.`title`, `t`.`description`
FROM `nf_shop_items` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_shop_items` DROP COLUMN `title`, DROP COLUMN `description`;
