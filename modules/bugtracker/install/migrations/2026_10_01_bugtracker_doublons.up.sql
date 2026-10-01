-- Un ticket peut etre marque « doublon » d'un autre (2026-10-01) : il renvoie a l'original, ou la
-- suite se passe, au lieu de rester un signalement concurrent.

ALTER TABLE `nf_bug_tickets` MODIFY `status` enum('open','in_progress','resolved','closed','wont_fix','duplicate') NOT NULL DEFAULT 'open';
ALTER TABLE `nf_bug_tickets` ADD COLUMN `duplicate_of` int(10) unsigned DEFAULT NULL AFTER `status`;
