ALTER TABLE `nf_articles` DROP KEY `idx_series`;
ALTER TABLE `nf_articles` DROP COLUMN `series_order`;
ALTER TABLE `nf_articles` DROP COLUMN `series_id`;
DROP TABLE IF EXISTS `nf_articles_series_lang`;
DROP TABLE IF EXISTS `nf_articles_series`;
