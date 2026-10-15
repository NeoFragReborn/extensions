-- NeoFrag Reborn — install du module « webradio » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_webradio_shows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(150) NOT NULL DEFAULT '',
  `day` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `start_time` varchar(5) NOT NULL DEFAULT '00:00',
  `end_time` varchar(5) NOT NULL DEFAULT '00:00',
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_grille` (`day`,`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_webradio_shows_lang` (
  `show_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`show_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_webradio_shows_lang_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `nf_webradio_shows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
