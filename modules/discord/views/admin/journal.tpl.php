<?php
/* Le journal du bot, le plus récent en haut : la date, puis le message précédé de son niveau. */
$journal = $journal ?? [];
$niveaux = [
	'info'  => ['text-muted', 'fas fa-info-circle'],
	'warn'  => ['text-warning', 'fas fa-exclamation-triangle'],
	'error' => ['text-danger', 'fas fa-times-circle'],
];
?>
<div class="table-responsive"><table class="table table-sm mb-0 align-middle">
	<tbody>
	<?php foreach ($journal as $l): [$classe, $icone] = $niveaux[$l['level']] ?? $niveaux['info'] ?>
	<tr>
		<td class="text-nowrap small text-muted ps-3"><?php echo htmlspecialchars((string) $l['created_at']) ?></td>
		<td class="small w-100" style="overflow-wrap:anywhere"><span class="<?php echo $classe ?> me-1"><?php echo icon($icone) ?></span><?php echo htmlspecialchars((string) $l['message']) ?></td>
	</tr>
	<?php endforeach ?>
	</tbody>
</table></div>
