<header class="bc-header">
	<?php echo $this->view('userbar') ?>
	<?php if ($zone = $this->output->region('header')): ?>
		<div class="bc-header-banner">
			<div class="container"><?php echo $zone ?></div>
		</div>
	<?php endif ?>
</header>

<?php if ($zone = $this->output->region('before_content')): ?>
	<section class="bc-section bc-avant-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<?php if ($zone = $this->output->region('content')): ?>
	<section class="bc-section bc-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<?php if ($zone = $this->output->region('after_content')): ?>
	<section class="bc-section bc-post-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<footer class="bc-footer">
	<div class="container">
		<?php echo $this->output->region('footer') ?>
		<div class="bc-footer-bar">
			<div class="bc-copy">
				<?php echo $this->lang('Propulsé par') ?> <a href="https://neofr.ag" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· © <?php echo date('Y') ?> <span class="bc-site-name"><?php echo $this->config->nf_name ?></span>
			</div>
			<?php echo $this->view('socials') ?>
			<?php echo nf_selecteur_theme() ?>
			<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
			<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="bc-lang dropup">
				<input type="hidden" name="url" value="<?php echo htmlspecialchars($this->url->base.trim($cur->name.'/'.nf_chemin_public(), '/').$this->url->query) ?>" />
				<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $cur->icon ?> <?php echo strtoupper($cur->name) ?></button>
				<div class="dropdown-menu dropdown-menu-end">
					<?php foreach ($this->config->langs as $l): $i = $l->info(); ?>
					<button type="submit" name="language" value="<?php echo $i->name ?>" class="dropdown-item<?php echo $i->name === $cur->name ? ' active' : '' ?>"><?php echo $i->icon ?> <?php echo htmlspecialchars((string)$i->title) ?></button>
					<?php endforeach ?>
				</div>
			</form>
			<?php endif ?>
		</div>
	</div>
</footer>
