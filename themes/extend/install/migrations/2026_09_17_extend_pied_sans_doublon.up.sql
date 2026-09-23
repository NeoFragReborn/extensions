-- Le thème Extend posait, dans la zone de pied de sa disposition livrée, un widget HTML
-- « Propulsé par NeoFrag Reborn » — exactement ce que son propre gabarit écrit deux lignes plus bas
-- (`ex-copy`, dans themes/extend/views/body.tpl.php). Un site en thème Extend affichait donc la
-- mention DEUX FOIS : une fois dans un panneau encadré, une fois dans la barre de pied.
--
-- Aucun des trois autres thèmes (forge, blockcraft, granite) ne pose de widget dans cette zone : ils
-- la déclarent pour que l'administrateur y mette ce qu'il veut, et s'en tiennent à leur propre ligne
-- de copyright. `themes/extend/extend.php::install()` s'aligne désormais ; cette migration débarrasse
-- les installations DÉJÀ en service, que l'installateur ne rejoue pas.
--
-- Les deux ordres sont écrits pour ne toucher QUE ce widget-là : on vide d'abord la disposition qui
-- le référence, puis on ne supprime la ligne de widget que si plus aucune disposition — quel que soit
-- le thème — ne s'en sert.

UPDATE `nf_dispositions` d
  JOIN `nf_widgets` w
    ON  w.`widget`   = 'html'
    AND w.`settings` LIKE '%Propuls%NeoFrag Reborn%'
   SET d.`disposition` = '[]'
 WHERE d.`theme` = 'extend'
   AND d.`disposition` LIKE CONCAT('%"id":', w.`widget_id`, ',%');

DELETE w FROM `nf_widgets` w
 WHERE w.`widget`   = 'html'
   AND w.`settings` LIKE '%Propuls%NeoFrag Reborn%'
   AND NOT EXISTS (
         SELECT 1
           FROM (SELECT `disposition` FROM `nf_dispositions`) x
          WHERE x.`disposition` LIKE CONCAT('%"id":', w.`widget_id`, ',%')
       );
