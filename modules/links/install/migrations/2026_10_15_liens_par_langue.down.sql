-- Retour arrière de 2026_10_15_liens_par_langue : les liens redeviennent monolingues.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_links`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `category_id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `url`;

ALTER TABLE `nf_links_categories`
  ADD COLUMN `title` varchar(150) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `nf_links` `k`
SET `k`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_links_lang` `l` WHERE `l`.`link_id` = `k`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `k`.`description` = (SELECT `l`.`description` FROM `nf_links_lang` `l` WHERE `l`.`link_id` = `k`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

UPDATE `nf_links_categories` `c`
SET `c`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_links_categories_lang` `l` WHERE `l`.`category_id` = `c`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_links_lang`;
DROP TABLE IF EXISTS `nf_links_categories_lang`;
