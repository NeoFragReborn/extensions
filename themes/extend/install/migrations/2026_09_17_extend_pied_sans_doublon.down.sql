-- Retour arrière volontairement INERTE, et il faut le dire plutôt que de faire semblant.
--
-- La migration montante retire un doublon d'affichage : une mention « Propulsé par NeoFrag Reborn »
-- qui faisait double emploi avec celle que le gabarit du thème écrit lui-même. Le remettre
-- reviendrait à réintroduire le défaut, et rien ne permet de distinguer, après coup, ce widget-là
-- d'un widget HTML que l'administrateur aurait lui-même placé dans son pied de page.
--
-- Un administrateur qui veut une mention supplémentaire dans son pied l'ajoute en deux clics depuis
-- l'éditeur en direct ; c'est un choix, pas un état à restaurer.

SELECT 1;
