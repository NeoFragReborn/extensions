-- NeoFrag Reborn — désinstall du module « quotes » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_quotes`;
DROP TABLE IF EXISTS `nf_quotes_categories`;

SET FOREIGN_KEY_CHECKS = 1;
