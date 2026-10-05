<div class="nf-field row">
	<label for="settings-effect" class="col-12 col-lg-4 col-form-label"><i class="far fa-snowflake"></i> <?php echo $this->lang('Effet') ?></label>
	<div class="col-12 col-lg-7">
		<select class="form-select" name="settings[effect]" id="settings-effect">
			<option value="none"<?php     if ($effect === 'none')     echo ' selected="selected"' ?>><?php echo $this->lang('Aucun') ?></option>
			<option value="snow"<?php     if ($effect === 'snow')     echo ' selected="selected"' ?>><?php echo $this->lang('Neige') ?></option>
			<option value="confetti"<?php if ($effect === 'confetti') echo ' selected="selected"' ?>><?php echo $this->lang('Confettis') ?></option>
			<option value="leaves"<?php   if ($effect === 'leaves')   echo ' selected="selected"' ?>><?php echo $this->lang('Feuilles') ?></option>
		</select>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-density" class="col-12 col-lg-4 col-form-label"><i class="fas fa-sliders-h"></i> <?php echo $this->lang('Densité') ?></label>
	<div class="col-12 col-lg-7">
		<select class="form-select" name="settings[density]" id="settings-density">
			<option value="low"<?php    if ($density === 'low')    echo ' selected="selected"' ?>><?php echo $this->lang('Discrète') ?></option>
			<option value="normal"<?php if ($density === 'normal') echo ' selected="selected"' ?>><?php echo $this->lang('Normale') ?></option>
			<option value="high"<?php   if ($density === 'high')   echo ' selected="selected"' ?>><?php echo $this->lang('Dense') ?></option>
		</select>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-from" class="col-12 col-lg-4 col-form-label"><i class="far fa-calendar-alt"></i> <?php echo $this->lang('Du') ?></label>
	<div class="col-12 col-lg-7">
		<input type="text" class="form-control" name="settings[from]" id="settings-from" value="<?php echo nf_texte($from) ?>" placeholder="12-15" pattern="\d{2}-\d{2}" />
	</div>
</div>
<div class="nf-field row">
	<label for="settings-to" class="col-12 col-lg-4 col-form-label"><i class="far fa-calendar-alt"></i> <?php echo $this->lang('Au') ?></label>
	<div class="col-12 col-lg-7">
		<input type="text" class="form-control" name="settings[to]" id="settings-to" value="<?php echo nf_texte($to) ?>" placeholder="01-06" pattern="\d{2}-\d{2}" />
		<small class="form-text text-muted"><?php echo $this->lang('Au format MM-JJ, sans année : la plage revient chaque année. Laissez les deux vides pour un effet permanent. Une plage peut enjamber le Nouvel An (du 12-15 au 01-06).') ?></small>
	</div>
</div>
