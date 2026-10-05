<?php
/* Une liste de correspondances (salon ↔ forum, groupe ↔ rôle), chacune avec son lien protégé pour la défaire. */
$lignes = $lignes ?? [];
$titres = $titres ?? ['', '', ''];
?>
<div class="table-responsive"><table class="table table-sm align-middle mb-0">
	<thead><tr><th class="ps-3"><?php echo $titres[0] ?></th><th></th><th><?php echo $titres[1] ?></th><th><?php echo $titres[2] ?></th><th></th></tr></thead>
	<tbody>
	<?php foreach ($lignes as $l): ?>
	<tr>
		<td class="ps-3"><?php echo nf_texte($l['gauche']) ?></td>
		<td class="text-muted"><?php echo icon('fas fa-exchange-alt') ?></td>
		<td><?php echo nf_texte($l['droite']) ?></td>
		<td class="small text-muted"><?php echo nf_texte($l['detail']) ?></td>
		<td class="text-end pe-3 text-nowrap"><?php if (!empty($l['etiquettes'])): ?><a class="btn btn-sm btn-outline-secondary me-1" href="<?php echo $l['etiquettes'] ?>" title="<?php echo nf_texte($this->lang('Préfixes et étiquettes')) ?>"><?php echo icon('fas fa-tags') ?></a><?php endif ?><a class="btn btn-sm btn-outline-danger" href="<?php echo $l['supprimer'] ?>" title="<?php echo nf_texte($this->lang('Défaire cette correspondance ?')) ?>" data-confirm="<?php echo nf_texte($this->lang('Défaire cette correspondance ?')) ?>"><?php echo icon('fas fa-unlink') ?></a></td>
	</tr>
	<?php endforeach ?>
	</tbody>
</table></div>
