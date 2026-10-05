<?php
/* L'aperçu de la mise en place : ce qui sera créé, ce qui sera repris parce qu'il existe déjà. */
$lignes    = $lignes ?? [];
$en_ligne  = $en_ligne ?? FALSE;
$appliquer = $appliquer ?? '#';
?>
<?php if (!$en_ligne): ?>
<div class="alert alert-warning small"><?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('Le bot n’est pas en ligne : la mise en place se fera à son retour. L’aperçu repose sur le serveur tel qu’il l’a vu en dernier.') ?></div>
<?php endif ?>
<div class="table-responsive mb-3"><table class="table table-sm align-middle mb-0">
	<thead><tr><th><?php echo $this->lang('Élément') ?></th><th><?php echo $this->lang('Nom') ?></th><th></th><th></th></tr></thead>
	<tbody>
	<?php foreach ($lignes as $l): ?>
	<tr>
		<td><?php echo nf_texte($l['quoi']) ?></td>
		<td><code><?php echo nf_texte($l['nom']) ?></code></td>
		<td><?php if ($l['repris']): ?><span class="badge text-bg-secondary"><?php echo $this->lang('Repris') ?></span><?php else: ?><span class="badge text-bg-success"><?php echo $this->lang('Créé') ?></span><?php endif ?></td>
		<td class="small text-muted"><?php echo nf_texte($l['detail']) ?></td>
	</tr>
	<?php endforeach ?>
	</tbody>
</table></div>
<p class="small text-muted"><?php echo $this->lang('Les correspondances (salon ↔ forum, groupe ↔ rôle) sont posées d’elles-mêmes. « Annuler la dernière mise en place » supprimera ce qui a été créé, et rien d’autre.') ?></p>
<div class="d-flex flex-wrap gap-2">
	<a class="btn btn-primary" href="<?php echo $appliquer ?>"><?php echo icon('fas fa-magic').' '.$this->lang('Appliquer') ?></a>
	<a class="btn btn-light" href="<?php echo url('admin/discord/mise-en-place') ?>"><?php echo icon('fas fa-pen').' '.$this->lang('Modifier') ?></a>
</div>
