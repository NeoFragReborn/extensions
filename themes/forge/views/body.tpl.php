<header class="fg-header">
	<?php echo $this->view('userbar') ?>
	<?php if ($zone = $this->output->zone(0)): ?>
		<div class="fg-header-banner">
			<div class="container"><?php echo $zone ?></div>
		</div>
	<?php endif ?>
</header>

<?php if ($zone = $this->output->zone(1)): ?>
	<section class="fg-section fg-avant-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<?php if ($zone = $this->output->zone(2)): ?>
	<section class="fg-section fg-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<?php if ($zone = $this->output->zone(3)): ?>
	<section class="fg-section fg-post-contenu">
		<div class="container"><?php echo $zone ?></div>
	</section>
<?php endif ?>

<footer class="fg-footer">
	<div class="container">
		<?php echo $this->output->zone(4) ?>
		<div class="fg-footer-bar">
			<div class="fg-copy">
				<?php echo $this->lang('Propulsé par') ?> <a href="https://neofr.ag" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· © <?php echo date('Y') ?> <span class="fg-site-name"><?php echo $this->config->nf_name ?></span>
			</div>
			<?php echo $this->view('socials') ?>
			<?php
			$nf_themes = array_map('strval', NeoFrag()->db->select('a.name')->from('nf_addon a')->join('nf_addon_type t', 't.id = a.type_id')->where('t.name', 'theme')->where('a.name !=', 'admin')->order_by('a.name')->get());
			if (count($nf_themes) > 1):
				$nf_cur = $this->config->nf_default_theme;
				if (!empty($_COOKIE['nf_theme']) && in_array($nf_c = preg_replace('/[^a-z0-9_]/i', '', (string) $_COOKIE['nf_theme']), $nf_themes, TRUE)) { $nf_cur = $nf_c; }
			?>
			<div class="nf-theme-switch dropup">
				<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo icon('fas fa-palette') ?> <?php echo htmlspecialchars(ucfirst($nf_cur)) ?></button>
				<div class="dropdown-menu dropdown-menu-end">
					<?php foreach ($nf_themes as $nf_t): ?>
					<button type="button" class="dropdown-item<?php echo $nf_t === $nf_cur ? ' active' : '' ?>" data-theme-pick="<?php echo htmlspecialchars($nf_t, ENT_QUOTES) ?>"><?php echo icon('fas fa-palette') ?> <?php echo htmlspecialchars(ucfirst($nf_t)) ?></button>
					<?php endforeach ?>
				</div>
			</div>
			<?php endif ?>
			<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
			<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="fg-lang dropup">
				<input type="hidden" name="url" value="<?php echo htmlspecialchars($this->url->base.implode('/', array_merge([$cur->name], $this->url->segments)).$this->url->query) ?>" />
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
