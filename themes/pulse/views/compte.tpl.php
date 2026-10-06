<?php
/*
 * Le compte, dans la barre de Pulse.
 *
 * Connecté : la cloche des notifications, la bascule jour / nuit, l'avatar et le pseudo qui ouvrent le menu de
 * l'espace membre — le même partout (User::menu_deroulant(), administration et déconnexion comprises). Visiteur : la
 * bascule et « Se connecter », un bouton au contour (l'appel « Adhérer », plein, suit dans la barre) ; au téléphone,
 * une icône qui garde son nom pour les lecteurs d'écran.
 */
?>
<ul class="pl-compte">
	<?php if ($this->user->id): ?>
		<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode nuit') ?>" aria-label="<?php echo $this->lang('Mode nuit') ?>"><i class="fas fa-moon"></i></button>
		</li>
		<li class="nav-item dropdown">
			<button type="button" class="nav-link pl-compte-membre" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?php echo $this->user->avatar() ?>
				<span class="pl-compte-nom"><?php echo nf_texte($this->user->username) ?></span><i class="fas fa-angle-down" aria-hidden="true"></i>
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
			<a class="pl-connexion" href="<?php echo url('user/login') ?>" data-modal-ajax="<?php echo url('ajax/user/login') ?>"><i class="fas fa-user" aria-hidden="true"></i><span class="pl-connexion-texte"><?php echo $this->lang('Se connecter') ?></span></a>
		</li>
	<?php endif ?>
</ul>
