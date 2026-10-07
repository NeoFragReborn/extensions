<?php
/*
 * Le compte, à droite de la barre d'Extend.
 *
 * Connecté : la cloche des notifications, la bascule nuit / jour, puis la pastille du membre — son avatar, son pseudo
 * et son palier de la gamification (s'il est installé : le rang est public, les points ne s'affichent pas) — qui ouvre
 * le menu de l'espace membre, le même partout (User::menu_deroulant(), administration et déconnexion comprises).
 * Visiteur : la bascule et « Se connecter » ; au téléphone, une icône qui garde son nom pour les lecteurs d'écran.
 *
 * couplage(gamification): le palier sous le pseudo ne s'affiche que si le module Gamification est installé.
 */
$palier = '';

if ($this->user->id && ($gamification = $this->module('gamification')) && $gamification->is_enabled())
{
	try
	{
		$palier = (string) $gamification->nom_palier((string) ($gamification->tier($gamification->get($this->user->id))['name'] ?? ''));
	}
	catch (\Throwable $e)
	{
		$palier = '';
	}
}
?>
<ul class="ex-compte-liste">
	<?php if ($this->user->id): ?>
		<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>"><i class="fas fa-sun"></i></button>
		</li>
		<li class="nav-item dropdown">
			<button type="button" class="nav-link ex-moi" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?php echo $this->user->avatar() ?>
				<span class="ex-moi-texte">
					<span class="ex-moi-nom"><?php echo nf_texte($this->user->username) ?></span>
					<?php if ($palier !== ''): ?><span class="ex-moi-palier"><?php echo nf_texte($palier) ?></span><?php endif ?>
				</span>
				<i class="fas fa-angle-down" aria-hidden="true"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-end">
				<?php echo $this->module('user')->menu_deroulant(TRUE) ?>
			</div>
		</li>
	<?php else: ?>
		<li class="nav-item">
			<button type="button" class="nav-link theme-toggle" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>"><i class="fas fa-sun"></i></button>
		</li>
		<li class="nav-item">
			<a class="ex-connexion" href="<?php echo url('user/login') ?>" data-modal-ajax="<?php echo url('ajax/user/login') ?>"><i class="fas fa-user" aria-hidden="true"></i><span class="ex-connexion-texte"><?php echo $this->lang('Se connecter') ?></span></a>
		</li>
	<?php endif ?>
</ul>
