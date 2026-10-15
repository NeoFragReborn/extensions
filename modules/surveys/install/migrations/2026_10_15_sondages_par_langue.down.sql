-- Retour arrière de 2026_10_15_sondages_par_langue : les sondages redeviennent monolingues.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte. Les votes, eux, ne bougent pas : ils ne pointent que sur
-- l'identifiant d'une réponse.

ALTER TABLE `nf_surveys`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `id`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `title`;

ALTER TABLE `nf_surveys_options`
  ADD COLUMN `label` varchar(200) NOT NULL DEFAULT '' AFTER `survey_id`;

UPDATE `nf_surveys` `s`
SET `s`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_surveys_lang` `l` WHERE `l`.`survey_id` = `s`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `s`.`description` = (SELECT `l`.`description` FROM `nf_surveys_lang` `l` WHERE `l`.`survey_id` = `s`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

UPDATE `nf_surveys_options` `o`
SET `o`.`label` = COALESCE((SELECT `l`.`label` FROM `nf_surveys_options_lang` `l` WHERE `l`.`option_id` = `o`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_surveys_lang`;
DROP TABLE IF EXISTS `nf_surveys_options_lang`;
