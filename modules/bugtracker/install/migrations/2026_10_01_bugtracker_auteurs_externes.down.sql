-- Retour sans auteurs venus d'ailleurs : leurs commentaires restent, anonymes.

ALTER TABLE `nf_bug_comments` DROP COLUMN `author_name`;
ALTER TABLE `nf_bug_comments` DROP COLUMN `author_external_id`;
ALTER TABLE `nf_bug_comments` DROP COLUMN `author_provider`;
