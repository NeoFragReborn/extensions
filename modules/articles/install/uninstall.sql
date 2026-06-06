-- NeoFrag Reborn — désinstall du module « articles » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `nf_articles_categories_lang`;
DROP TABLE IF EXISTS `nf_articles_categories`;
DROP TABLE IF EXISTS `nf_articles_lang`;
DROP TABLE IF EXISTS `nf_articles`;

SET FOREIGN_KEY_CHECKS = 1;
