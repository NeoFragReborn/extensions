<?php
/*
 * Le compte, au pied du rail (Forge « Coulée ») ; au téléphone, à droite de la barre du haut.
 *
 * Connecté : l'avatar (qui mène au profil public), le pseudo et « Mon espace », un bouton qui ouvre le menu de
 * l'espace membre — le même partout (User::menu_deroulant(), administration et déconnexion comprises) —, la
 * cloche des notifications et la bascule jour / nuit. Visiteur : l'accès à l'espace membre et la bascule.
 */
?>
<div class="fg-compte<?php echo $this->user->id ? ' fg-compte-connecte' : '' ?>" data-nf-entete>
	<?php if ($this->user->id): ?>
		<div class="fg-compte-identite">
			<?php echo $this->user->avatar() ?>
			<a class="fg-compte-nom" href="<?php echo url('user') ?>">
				<b><?php echo nf_texte($this->user->username) ?></b>
				<small><?php echo $this->lang('Mon espace') ?></small>
			</a>
		</div>
		<ul class="fg-compte-actions">
			<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
			<li class="nav-item">
				<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>"><i class="fas fa-sun"></i></button>
			</li>
			<li class="nav-item dropup">
				<button type="button" class="nav-link fg-compte-menu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?php echo $this->lang('Mon compte') ?>" aria-label="<?php echo $this->lang('Mon compte') ?>"><i class="fas fa-ellipsis-vertical"></i></button>
				<div class="dropdown-menu dropdown-menu-end">
					<?php echo $this->module('user')->menu_deroulant(TRUE) ?>
				</div>
			</li>
		</ul>
	<?php else: ?>
		<a class="fg-compte-entrer" href="<?php echo url('user') ?>"><?php echo icon('fas fa-right-to-bracket') ?> <span><?php echo $this->lang('Espace membre') ?></span></a>
		<ul class="fg-compte-actions">
			<li class="nav-item">
				<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>"><i class="fas fa-sun"></i></button>
			</li>
		</ul>
	<?php endif ?>
</div>
