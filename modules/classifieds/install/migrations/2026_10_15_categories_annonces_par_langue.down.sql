-- Retour arrière de 2026_10_15_categories_annonces_par_langue : Le nom d'une rubrique de petites annonces redevient monolingue.
--
-- La colonne revient, remplie de la MEILLEURE traduction gardée (le français d'abord, sinon la première écrite) :
-- on perd les autres langues, pas le texte.

ALTER TABLE `nf_classifieds_categories` ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `nf_classifieds_categories` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_classifieds_categories_lang` `l` WHERE `l`.`category_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_classifieds_categories_lang`;
