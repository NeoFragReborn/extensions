-- Retour sans statut « doublon » : ces tickets redeviennent « fermes ».

UPDATE `nf_bug_tickets` SET `status` = 'closed' WHERE `status` = 'duplicate';
ALTER TABLE `nf_bug_tickets` DROP COLUMN `duplicate_of`;
ALTER TABLE `nf_bug_tickets` MODIFY `status` enum('open','in_progress','resolved','closed','wont_fix') NOT NULL DEFAULT 'open';
