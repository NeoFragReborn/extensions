-- NeoFrag Reborn — désinstall du module « articles » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_articles_series_lang`;
DROP TABLE IF EXISTS `nf_articles_series`;
DROP TABLE IF EXISTS `nf_articles_categories_lang`;
DROP TABLE IF EXISTS `nf_articles_categories`;
DROP TABLE IF EXISTS `nf_articles_lang`;
DROP TABLE IF EXISTS `nf_articles`;

SET FOREIGN_KEY_CHECKS = 1;
