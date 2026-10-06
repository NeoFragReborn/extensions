<?php
/*
 * Le compte, au bout de la ligne de la date (Granite « Gazette »).
 *
 * Connecté : la cloche des notifications, la bascule jour / nuit, l'avatar et le pseudo qui ouvrent le menu de
 * l'espace membre — le même partout (User::menu_deroulant(), administration et déconnexion comprises). Visiteur :
 * la bascule et l'accès à l'espace membre.
 */
?>
<ul class="gz-compte">
	<?php if ($this->user->id): ?>
		<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode nuit') ?>" aria-label="<?php echo $this->lang('Mode nuit') ?>"><i class="fas fa-moon"></i></button>
		</li>
		<li class="nav-item dropdown">
			<button type="button" class="nav-link gz-compte-membre" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<span class="gz-compte-nom"><?php echo nf_texte($this->user->username) ?></span><i class="fas fa-angle-down" aria-hidden="true"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-end">
				<?php echo $this->module('user')->menu_deroulant(TRUE) ?>
			</div>
		</li>
	<?php else: ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode nuit') ?>" aria-label="<?php echo $this->lang('Mode nuit') ?>"><i class="fas fa-moon"></i></button>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="<?php echo url('user') ?>"><?php echo $this->lang('Espace membre') ?></a>
		</li>
	<?php endif ?>
</ul>
