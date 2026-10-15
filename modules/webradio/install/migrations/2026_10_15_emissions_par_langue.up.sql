-- Le titre et la description d'une émission de la webradio se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le titre et la description d'une émission de la webradio n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions, comme les téléchargements, les liens, la FAQ, les sondages et les types de rendez-vous ; les pages
-- servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : le jour, l’heure, l’anim ateur et l’image.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_webradio_shows_lang` (
  `show_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`show_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_webradio_shows_lang_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `nf_webradio_shows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_webradio_shows_lang` (`show_id`, `lang`, `title`, `description`)
SELECT `t`.`id`, `l`.`name`, `t`.`title`, `t`.`description`
FROM `nf_webradio_shows` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_webradio_shows` DROP COLUMN `title`, DROP COLUMN `description`;
