-- Retour arrière de 2026_10_15_telechargements_par_langue : les téléchargements redeviennent monolingues.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_downloads`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `category_id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `title`;

ALTER TABLE `nf_downloads_categories`
  ADD COLUMN `title` varchar(150) NOT NULL DEFAULT '' AFTER `id`,
  ADD COLUMN `description` varchar(500) DEFAULT NULL AFTER `title`;

UPDATE `nf_downloads` `d`
SET `d`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_downloads_lang` `l` WHERE `l`.`download_id` = `d`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `d`.`description` = (SELECT `l`.`description` FROM `nf_downloads_lang` `l` WHERE `l`.`download_id` = `d`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

UPDATE `nf_downloads_categories` `c`
SET `c`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_downloads_categories_lang` `l` WHERE `l`.`category_id` = `c`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `c`.`description` = (SELECT `l`.`description` FROM `nf_downloads_categories_lang` `l` WHERE `l`.`category_id` = `c`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_downloads_lang`;
DROP TABLE IF EXISTS `nf_downloads_categories_lang`;
