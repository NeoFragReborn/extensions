<div class="nf-field row">
	<label for="settings-placement" class="col-12 col-lg-3 col-form-label"><?php echo $this->lang('Emplacement') ?></label>
	<div class="col-12 col-lg-6">
		<div class="input-group">
			<div class="input-group-text"><?php echo icon('fas fa-rectangle-ad') ?></div>
			<input type="text" class="form-control" name="settings[placement]" value="<?php echo nf_texte($placement ?: 'sidebar') ?>" placeholder="sidebar" />
		</div>
		<small class="form-text text-muted"><?php echo $this->lang('Slot d\'annonce : les annonces de la régie avec cet emplacement seront affichées ici (ex. sidebar, footer, header).') ?></small>
	</div>
</div>
