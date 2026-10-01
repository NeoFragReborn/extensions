-- NeoFrag Reborn — install du module « discord » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_discord_channels` (
  `mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` varchar(20) NOT NULL,
  `forum_id` int(10) unsigned NOT NULL,
  `mode` enum('all','reaction') NOT NULL DEFAULT 'all',
  `emoji` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `uk_channel` (`channel_id`),
  UNIQUE KEY `uk_forum` (`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_links` (
  `link_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('topic','message') NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  `discord_id` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`link_id`),
  UNIQUE KEY `uk_site` (`type`,`site_id`),
  UNIQUE KEY `uk_discord` (`type`,`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_logs` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `level` enum('info','warn','error') NOT NULL DEFAULT 'info',
  `message` varchar(1000) NOT NULL,
  `template` varchar(500) DEFAULT NULL,
  `args` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_roles` (
  `mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_key` varchar(100) NOT NULL,
  `role_id` varchar(20) NOT NULL,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `uk_group` (`group_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_state` (
  `name` varchar(50) NOT NULL,
  `value` mediumtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
