-- Retour à la v1 du 1.2.11 : sans liens de liaison, étiquettes, rôles temporaires ni tickets.

DELETE FROM `nf_discord_links` WHERE `type` IN ('ticket', 'comment');
ALTER TABLE `nf_discord_links` MODIFY `type` enum('topic','message') NOT NULL;
DROP TABLE IF EXISTS `nf_discord_timed_roles`;
DROP TABLE IF EXISTS `nf_discord_tags`;
DROP TABLE IF EXISTS `nf_discord_link_tokens`;
