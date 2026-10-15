-- Les sondages se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- La question, sa description et le libellé de ses réponses vivaient dans `nf_surveys` et
-- `nf_surveys_options` : un site en six langues les montrait dans une seule — relevé sur la démonstration en
-- anglais, qui demandait « Quel jeu pour le prochain tournoi ? ». Ils passent dans deux tables de traductions ;
-- les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première
-- écrite).
--
-- Ce qui ne se traduit PAS reste où il est : l'auteur, le choix multiple, la visibilité des résultats, la
-- fermeture, la publication, l'ordre des réponses — et les VOTES, qui n'ont jamais eu de langue.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_surveys_lang` (
  `survey_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`survey_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_surveys_lang_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `nf_surveys` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_surveys_options_lang` (
  `option_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `label` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`option_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_surveys_options_lang_ibfk_1` FOREIGN KEY (`option_id`) REFERENCES `nf_surveys_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_surveys_lang` (`survey_id`, `lang`, `title`, `description`)
SELECT `s`.`id`, `l`.`name`, `s`.`title`, `s`.`description`
FROM `nf_surveys` `s`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

INSERT IGNORE INTO `nf_surveys_options_lang` (`option_id`, `lang`, `label`)
SELECT `o`.`id`, `l`.`name`, `o`.`label`
FROM `nf_surveys_options` `o`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_surveys` DROP COLUMN `title`, DROP COLUMN `description`;
ALTER TABLE `nf_surveys_options` DROP COLUMN `label`;
