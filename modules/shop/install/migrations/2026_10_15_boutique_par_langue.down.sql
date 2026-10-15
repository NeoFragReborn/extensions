-- Retour arrière de 2026_10_15_boutique_par_langue : Le nom et la description d'un article de la boutique redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_shop_items`
  ADD COLUMN `title` varchar(150) NOT NULL DEFAULT '' AFTER `id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `title`;

UPDATE `nf_shop_items` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_shop_items_lang` `l` WHERE `l`.`item_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `t`.`description` = (SELECT `l`.`description` FROM `nf_shop_items_lang` `l` WHERE `l`.`item_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_shop_items_lang`;
