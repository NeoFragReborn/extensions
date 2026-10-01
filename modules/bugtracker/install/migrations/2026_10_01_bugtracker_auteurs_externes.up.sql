-- Un commentaire venu de Discord (2026-10-01) peut être écrit par quelqu'un qui n'a pas
-- lié son compte au site : il n'appartient alors à aucun membre, mais garde le nom et l'identifiant
-- de son auteur sur Discord — l'affichage le montre, et seul cet auteur peut le corriger ou l'effacer.

ALTER TABLE `nf_bug_comments` ADD COLUMN `author_provider` varchar(20) DEFAULT NULL AFTER `is_status_change`;
ALTER TABLE `nf_bug_comments` ADD COLUMN `author_external_id` varchar(64) DEFAULT NULL AFTER `author_provider`;
ALTER TABLE `nf_bug_comments` ADD COLUMN `author_name` varchar(100) DEFAULT NULL AFTER `author_external_id`;
