-- NeoFrag Reborn — install du module « articles » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_articles` (
  `article_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `series_id` int(10) unsigned DEFAULT NULL,
  `series_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL,
  `image_id` int(10) unsigned DEFAULT NULL,
  -- datetime : une `date` future programme/masque la publication (cf. models/articles.php). TIMESTAMP
  -- plafonne au 19/01/2038 → rejet en mode strict. `announced_at` reste TIMESTAMP (posé à la parution réelle).
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `published` enum('0','1') NOT NULL DEFAULT '0',
  `featured` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `announced_at` timestamp NULL DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`article_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_image` (`image_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_schedule` (`published`,`announced_at`,`date`),
  KEY `idx_series` (`series_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_articles_lang` (
  `article_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL,
  `excerpt` varchar(500) NOT NULL DEFAULT '',
  `content` mediumtext NOT NULL,
  `tags` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`article_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_articles_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` int(10) unsigned DEFAULT NULL,
  `icon_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `idx_image` (`image_id`),
  KEY `idx_icon` (`icon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_articles_categories_lang` (
  `category_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_articles_series` (
  `series_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`series_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_articles_series_lang` (
  `series_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`series_id`,`lang`),
  CONSTRAINT `nf_articles_series_lang_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `nf_articles_series` (`series_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
