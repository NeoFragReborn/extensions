<div class="tab-content">
	<div class="tab-pane fade show active" id="<?php echo $theme->info()->name ?>-dashboard" role="tabpanel">
		<div class="row">
			<div class="col-md-12 col-lg-4">
				<img class="img-fluid rounded" src="<?php echo image('thumbnail.jpg', $this->theme($theme->info()->name)) ?>" alt="<?php echo $theme->info()->title ?>" />
			</div>
			<div class="col-md-12 col-lg-8">
				<dl class="row mb-0 mt-3 mt-lg-0">
					<dt class="col-sm-3"><?php echo $this->lang('Nom') ?></dt>
					<dd class="col-sm-9"><?php echo $theme->info()->title ?></dd>
					<dt class="col-sm-3"><?php echo $this->lang('Description') ?></dt>
					<dd class="col-sm-9"><?php echo $theme->info()->description ?></dd>
					<dt class="col-sm-3"><?php echo $this->lang('Version') ?></dt>
					<dd class="col-sm-9"><code><?php echo $theme->info()->version ?></code></dd>
					<dt class="col-sm-3"><?php echo $this->lang('Auteur') ?></dt>
					<dd class="col-sm-9"><?php echo $theme->info()->author ?></dd>
					<dt class="col-sm-3"><?php echo $this->lang('Licence') ?></dt>
					<dd class="col-sm-9"><?php echo $theme->info()->license ?></dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="<?php echo $theme->info()->name ?>-settings"   role="tabpanel"><?php echo $form_settings ?></div>
	<div class="tab-pane fade" id="<?php echo $theme->info()->name ?>-header"     role="tabpanel"><?php echo $form_header ?></div>
	<div class="tab-pane fade" id="<?php echo $theme->info()->name ?>-background" role="tabpanel"><?php echo $form_background ?></div>
	<div class="tab-pane fade" id="<?php echo $theme->info()->name ?>-socials"    role="tabpanel"><?php echo $form_socials ?></div>
</div>
