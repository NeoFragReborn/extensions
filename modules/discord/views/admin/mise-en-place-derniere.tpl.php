<?php
/* La dernière mise en place, telle que le bot l'a rapportée, et de quoi l'annuler. */
$derniere = $derniere ?? [];
$annuler  = $annuler ?? '#';
$cree     = (array) ($derniere['cree'] ?? []);
$nombre   = count((array) ($cree['salons'] ?? [])) + count((array) ($cree['roles'] ?? [])) + (!empty($cree['categorie']) ? 1 : 0);
?>
<p class="mb-2"><?php echo $this->lang('Le %s : %d élément(s) créé(s), %d repris.', htmlspecialchars(timetostr('d/m/Y H:i', (int) ($derniere['at'] ?? 0))), $nombre, (int) ($derniere['repris'] ?? 0)) ?></p>
<?php if (!empty($derniere['erreurs'])): ?>
<div class="alert alert-danger small mb-2">
	<?php echo $this->lang('Ce que le bot n’a pas pu faire :') ?>
	<ul class="mb-0"><?php foreach ((array) $derniere['erreurs'] as $e): ?><li><?php echo htmlspecialchars((string) $e) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>
<?php if ($nombre): ?>
<a class="btn btn-outline-danger btn-sm" href="<?php echo $annuler ?>" data-confirm="<?php echo htmlspecialchars((string) $this->lang('Supprimer du serveur Discord ce que cette mise en place a créé ? Les messages qu’ils contiennent seront perdus.'), ENT_QUOTES) ?>"><?php echo icon('fas fa-undo').' '.$this->lang('Annuler la dernière mise en place') ?></a>
<?php endif ?>
