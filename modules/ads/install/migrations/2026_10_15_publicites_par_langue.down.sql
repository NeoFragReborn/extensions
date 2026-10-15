-- Retour arrière de 2026_10_15_publicites_par_langue : Le titre d'une publicité redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_ads`
  ADD COLUMN `title` varchar(150) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `nf_ads` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_ads_lang` `l` WHERE `l`.`ad_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_ads_lang`;
