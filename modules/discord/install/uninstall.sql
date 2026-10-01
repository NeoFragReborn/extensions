-- NeoFrag Reborn — désinstall du module « discord » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_discord_state`;
DROP TABLE IF EXISTS `nf_discord_roles`;
DROP TABLE IF EXISTS `nf_discord_logs`;
DROP TABLE IF EXISTS `nf_discord_links`;
DROP TABLE IF EXISTS `nf_discord_channels`;

SET FOREIGN_KEY_CHECKS = 1;
