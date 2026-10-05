<?php
/* Une fonctionnalité du bot dans la liste : son titre, ce qu'elle fait, son interrupteur et ses réglages. */
$titre       = $titre ?? '';
$description = $description ?? '';
$nom         = $nom ?? '';
$active      = $active ?? FALSE;
$reglages    = $reglages ?? 0;
$basculer    = $basculer ?? '#';
?>
<div class="d-flex flex-wrap align-items-center gap-3 border-top py-3">
	<div style="flex:1 1 14rem;min-width:0">
		<div class="fw-semibold"><?php echo nf_texte($titre) ?>
			<?php if ($active): ?><span class="badge text-bg-success ms-1"><?php echo $this->lang('Allumée') ?></span><?php else: ?><span class="badge text-bg-secondary ms-1"><?php echo $this->lang('Éteinte') ?></span><?php endif ?>
		</div>
		<div class="small text-muted"><?php echo nf_texte($description) ?></div>
	</div>
	<div class="d-flex flex-wrap flex-shrink-0 gap-2">
		<?php if ($reglages): ?>
		<a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/discord/fonctionnalite/'.$nom) ?>"><?php echo icon('fas fa-sliders-h').' '.$this->lang('Réglages') ?></a>
		<?php endif ?>
		<?php if ($active): ?>
		<a class="btn btn-outline-warning btn-sm" href="<?php echo $basculer ?>"><?php echo icon('fas fa-power-off').' '.$this->lang('Éteindre') ?></a>
		<?php else: ?>
		<a class="btn btn-success btn-sm" href="<?php echo $basculer ?>"><?php echo icon('fas fa-power-off').' '.$this->lang('Allumer') ?></a>
		<?php endif ?>
	</div>
</div>
