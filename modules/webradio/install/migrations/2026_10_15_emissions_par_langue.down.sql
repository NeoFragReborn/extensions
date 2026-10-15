-- Retour arrière de 2026_10_15_emissions_par_langue : Le titre et la description d'une émission de la webradio redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_webradio_shows`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `title`;

UPDATE `nf_webradio_shows` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_webradio_shows_lang` `l` WHERE `l`.`show_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `t`.`description` = COALESCE((SELECT `l`.`description` FROM `nf_webradio_shows_lang` `l` WHERE `l`.`show_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_webradio_shows_lang`;
