-- Les series du Blog (2026-10-01) : un billet en plusieurs parties, avec leur navigation.
-- Une serie a un titre et une presentation par langue ; un billet en est une partie, a son rang.

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

ALTER TABLE `nf_articles` ADD COLUMN `series_id` int(10) unsigned DEFAULT NULL AFTER `category_id`;
ALTER TABLE `nf_articles` ADD COLUMN `series_order` smallint(5) unsigned NOT NULL DEFAULT 0 AFTER `series_id`;
ALTER TABLE `nf_articles` ADD KEY `idx_series` (`series_id`);
