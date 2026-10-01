<?php
/* La page de liaison : le compte Discord, le compte du site, et la confirmation — ou de quoi se connecter d'abord. */
$liaison   = $liaison ?? [];
$connecte  = $connecte ?? FALSE;
$membre    = $membre ?? '';
$messages  = $messages ?? 0;
$relier    = $relier ?? '#';
$reprendre = $reprendre ?? '';
?>
<div class="d-flex align-items-center gap-3 mb-3">
	<?php if (!empty($liaison['avatar'])): ?>
	<img src="<?php echo htmlspecialchars((string) $liaison['avatar']) ?>" alt="" width="56" height="56" class="rounded-circle" loading="lazy" />
	<?php else: ?>
	<span class="fs-1 text-muted"><?php echo icon('fab fa-discord') ?></span>
	<?php endif ?>
	<div>
		<div class="small text-muted"><?php echo $this->lang('Compte Discord') ?></div>
		<div class="fw-semibold"><?php echo htmlspecialchars((string) ($liaison['username'] ?? '')) ?></div>
	</div>
	<?php if ($connecte): ?>
	<span class="text-muted fs-4"><?php echo icon('fas fa-link') ?></span>
	<div>
		<div class="small text-muted"><?php echo $this->lang('Compte du site') ?></div>
		<div class="fw-semibold"><?php echo htmlspecialchars((string) $membre) ?></div>
	</div>
	<?php endif ?>
</div>
<?php if ($connecte): ?>
<p><?php echo $this->lang('Une fois vos comptes reliés, ce que vous écrivez depuis Discord paraît sur le forum sous votre compte du site, et le serveur Discord vous donne les rôles de vos groupes.') ?></p>
<div class="d-flex flex-wrap gap-2">
	<?php if ($reprendre !== ''): ?>
	<a class="btn btn-primary" href="<?php echo $reprendre ?>"><?php echo icon('fas fa-link').' '.$this->lang('Relier, et reprendre à mon nom mes %d message(s) publiés depuis Discord', $messages) ?></a>
	<a class="btn btn-outline-primary" href="<?php echo $relier ?>"><?php echo icon('fas fa-link').' '.$this->lang('Relier seulement') ?></a>
	<?php else: ?>
	<a class="btn btn-primary" href="<?php echo $relier ?>"><?php echo icon('fas fa-link').' '.$this->lang('Relier mes comptes') ?></a>
	<?php endif ?>
</div>
<?php else: ?>
<p><?php echo $this->lang('Pour relier ce compte Discord, connectez-vous à votre compte du site — ou créez-en un —, puis revenez sur ce lien.') ?></p>
<div class="d-flex flex-wrap gap-2">
	<a class="btn btn-primary" href="<?php echo url('user/login') ?>"><?php echo icon('fas fa-sign-in-alt').' '.$this->lang('Se connecter') ?></a>
	<a class="btn btn-outline-primary" href="<?php echo url('user/registration') ?>"><?php echo icon('fas fa-user-plus').' '.$this->lang('Créer un compte') ?></a>
</div>
<?php endif ?>
