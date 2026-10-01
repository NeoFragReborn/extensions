-- Le bot Discord, suite de la v1 (2026-10-01) :
--   - les liens à usage unique de `/forum account link` (relier son Discord depuis Discord) ;
--   - les correspondances préfixe du forum ↔ étiquette d'un salon Forum ;
--   - les rôles donnés pour une durée, que le bot retire à l'échéance ;
--   - les tickets du Bugtracker et leurs commentaires, liés à leurs fils et messages Discord.

CREATE TABLE IF NOT EXISTS `nf_discord_link_tokens` (
  `token_hash` char(64) NOT NULL,
  `discord_id` varchar(20) NOT NULL,
  `username` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token_hash`),
  KEY `idx_discord` (`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_tags` (
  `mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` varchar(20) NOT NULL,
  `prefix_id` int(10) unsigned NOT NULL,
  `tag_id` varchar(20) NOT NULL,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `uk_prefix` (`channel_id`,`prefix_id`),
  UNIQUE KEY `uk_tag` (`channel_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_discord_timed_roles` (
  `timed_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `discord_id` varchar(20) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `role_id` varchar(20) NOT NULL,
  `expires_at` datetime NOT NULL,
  `given_by` varchar(20) DEFAULT NULL,
  `given_by_name` varchar(100) DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`timed_id`),
  UNIQUE KEY `uk_member_role` (`discord_id`,`role_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `nf_discord_links` MODIFY `type` enum('topic','message','ticket','comment') NOT NULL;
