<?php
/*
 * Le compte, dans l'en-tête de Chronique.
 *
 * Connecté : la cloche des notifications, la bascule jour / nuit, l'avatar et le pseudo qui ouvrent le menu de
 * l'espace membre — le même partout (User::menu_deroulant(), administration et déconnexion comprises). Visiteur : la
 * bascule et la connexion (l'appel à adhérer suit, dans l'en-tête).
 */
?>
<ul class="ch-compte">
	<?php if ($this->user->id): ?>
		<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode nuit') ?>" aria-label="<?php echo $this->lang('Mode nuit') ?>"><i class="fas fa-moon"></i></button>
		</li>
		<li class="nav-item dropdown">
			<button type="button" class="nav-link ch-compte-membre" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?php echo $this->user->avatar() ?>
				<span class="ch-compte-nom"><?php echo nf_texte($this->user->username) ?></span><i class="fas fa-angle-down" aria-hidden="true"></i>
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
			<?php /* Au téléphone, une icône : le mot ne tient plus, et la connexion ne doit pas disparaître de l'en-tête
			         (2026-10-06 — elle n'était plus qu'au bas des pages, dans le panneau de l'espace membre). */ ?>
			<a class="nav-link ch-connexion" href="<?php echo url('user/login') ?>" data-modal-ajax="<?php echo url('ajax/user/login') ?>"><i class="fas fa-user" aria-hidden="true"></i><span class="ch-connexion-texte"><?php echo $this->lang('Connexion') ?></span></a>
		</li>
	<?php endif ?>
</ul>
