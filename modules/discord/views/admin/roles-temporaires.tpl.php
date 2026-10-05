<?php
/*
 * Les rôles temporaires en cours, donnés sur Discord par `/role give` : qui, quel rôle, jusqu'à quand,
 * par qui et pourquoi — et le bouton qui avance l'échéance à maintenant (le bot retire alors le rôle
 * à son prochain passage, d'ici une minute).
 */
$lignes = $lignes ?? [];
?>
<div class="table-responsive"><table class="table table-sm align-middle mb-0">
	<thead><tr>
		<th class="ps-3"><?php echo $this->lang('Membre') ?></th>
		<th><?php echo $this->lang('Rôle Discord') ?></th>
		<th><?php echo $this->lang('Fin') ?></th>
		<th><?php echo $this->lang('Donné par') ?></th>
		<th><?php echo $this->lang('Raison') ?></th>
		<th></th>
	</tr></thead>
	<tbody>
	<?php foreach ($lignes as $l): ?>
	<tr>
		<td class="ps-3"><?php echo $l['membre'] ?></td>
		<td><?php echo nf_texte($l['role']) ?></td>
		<td class="text-nowrap"><?php echo $l['echu'] ? '<span class="badge text-bg-warning">'.$this->lang('Retrait en cours').'</span>' : nf_texte($l['fin']) ?></td>
		<td class="small"><?php echo nf_texte($l['par']) ?></td>
		<td class="small text-muted"><?php echo nf_texte($l['raison']) ?></td>
		<td class="text-end pe-3 text-nowrap"><?php if (!$l['echu']): ?><a class="btn btn-sm btn-outline-danger" href="<?php echo $l['retirer'] ?>" data-confirm="<?php echo nf_texte($this->lang('Retirer ce rôle maintenant ? Le bot le retirera d’ici une minute.')) ?>"><?php echo icon('fas fa-user-minus').' '.$this->lang('Retirer maintenant') ?></a><?php endif ?></td>
	</tr>
	<?php endforeach ?>
	</tbody>
</table></div>
