<div class="nf-field row">
	<label for="settings-url" class="col-12 col-lg-4 col-form-label"><i class="fas fa-rss"></i> <?php echo $this->lang('Adresse du flux') ?></label>
	<div class="col-12 col-lg-7">
		<input type="url" class="form-control" name="settings[url]" id="settings-url" value="<?php echo nf_texte($url) ?>" placeholder="https://exemple.org/feed.xml" />
		<small class="form-text text-muted"><?php echo $this->lang('Flux RSS 2.0 ou Atom, en http ou https. Le serveur refuse les adresses internes au réseau.') ?></small>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-title" class="col-12 col-lg-4 col-form-label"><i class="fas fa-heading"></i> <?php echo $this->lang('Titre affiché') ?></label>
	<div class="col-12 col-lg-7">
		<input type="text" class="form-control" name="settings[title]" id="settings-title" value="<?php echo nf_texte($title) ?>" maxlength="80" />
		<small class="form-text text-muted"><?php echo $this->lang('Laissez vide pour reprendre le titre annoncé par le flux.') ?></small>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-count" class="col-12 col-lg-4 col-form-label"><i class="fas fa-list-ol"></i> <?php echo $this->lang('Nombre d\'articles') ?></label>
	<div class="col-12 col-lg-7">
		<input type="number" class="form-control" name="settings[count]" id="settings-count" value="<?php echo (int) $count ?>" min="1" max="20" />
	</div>
</div>
<div class="nf-field row">
	<label for="settings-ttl" class="col-12 col-lg-4 col-form-label"><i class="fas fa-clock"></i> <?php echo $this->lang('Durée du cache') ?></label>
	<div class="col-12 col-lg-7">
		<select class="form-select" name="settings[ttl]" id="settings-ttl">
			<?php foreach ($durees as $nf_duree): ?>
			<option value="<?php echo (int) $nf_duree ?>"<?php if ((int) $ttl === (int) $nf_duree) echo ' selected="selected"' ?>><?php echo $this->lang('%d minutes', (int) ($nf_duree / 60)) ?></option>
			<?php endforeach ?>
		</select>
		<small class="form-text text-muted"><?php echo $this->lang('Le site distant n\'est interrogé qu\'une fois par période, et jamais pendant qu\'une page se rend : le rafraîchissement a lieu dans la tâche planifiée.') ?></small>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-show-date" class="col-12 col-lg-4 col-form-label"><i class="far fa-clock"></i> <?php echo $this->lang('Afficher la date') ?></label>
	<div class="col-12 col-lg-7">
		<?php /* Une liste plutôt qu'une case à cocher : une case décochée n'envoie RIEN, et le réglage
		         retomberait alors sur son défaut au lieu d'être désactivé. C'est l'idiome du produit
		         (cf. widgets/twitch). */ ?>
		<select class="form-select" name="settings[show_date]" id="settings-show-date">
			<option value="1"<?php if ($show_date)  echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
			<option value="0"<?php if (!$show_date) echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
		</select>
	</div>
</div>
<div class="nf-field row">
	<label for="settings-show-summary" class="col-12 col-lg-4 col-form-label"><i class="fas fa-align-left"></i> <?php echo $this->lang('Afficher le résumé') ?></label>
	<div class="col-12 col-lg-7">
		<select class="form-select" name="settings[show_summary]" id="settings-show-summary">
			<option value="1"<?php if ($show_summary)  echo ' selected="selected"' ?>><?php echo $this->lang('Oui') ?></option>
			<option value="0"<?php if (!$show_summary) echo ' selected="selected"' ?>><?php echo $this->lang('Non') ?></option>
		</select>
	</div>
</div>
