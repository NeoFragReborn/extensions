-- Les liens Discord d'un élément supprimé (m21, 2026-10-10) : un ticket, un sujet, un message ou un commentaire effacé
-- laissait son lien dans nf_discord_links. Le ménage du jour note quand un lien a perdu son élément, et le retire sept
-- jours plus tard — le bot, qui lit le lien pour effacer le fil sur Discord, a eu le temps de le faire.

ALTER TABLE `nf_discord_links` ADD COLUMN `orphelin_depuis` datetime DEFAULT NULL AFTER `created_at`;
