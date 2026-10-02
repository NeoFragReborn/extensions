<?php
/* Les réglages du bot en un coup d'œil, et les actions qui les changent. Les sous-pages sont dans les
   onglets en haut de page (_onglets()). */
$reglages = $reglages ?? [];
$cle_bot  = $cle_bot ?? FALSE;
?>
<ul class="list-unstyled small mb-3">
	<li><?php echo $this->lang('Clé du bot : %s', !empty($reglages['token_set']) ? $this->lang('enregistrée, chiffrée') : $this->lang('à renseigner')) ?></li>
	<li><?php echo $this->lang('Serveur : %s', !empty($reglages['guild_id']) ? htmlspecialchars((string) $reglages['guild_id']) : $this->lang('à renseigner')) ?></li>
	<li><?php echo $this->lang('Clé d’accès du bot au site : %s', $cle_bot ? $this->lang('créée') : $this->lang('à créer')) ?></li>
	<li><?php echo $this->lang('%d salon(s) relié(s) à un forum, %d groupe(s) relié(s) à un rôle', (int) ($salons ?? 0), (int) ($roles ?? 0)) ?></li>
</ul>
<?php if (!empty($droits_manquants)): ?>
<div class="alert alert-warning small"><?php echo icon('fas fa-key').' '.$this->lang('La clé d’accès du bot date d’une version précédente : il lui manque des droits (%s). Créez-en une nouvelle, puis remplacez-la sur la machine du bot.', htmlspecialchars(implode(', ', (array) $droits_manquants))) ?></div>
<?php endif ?>
<div class="d-flex flex-wrap gap-2">
	<a class="btn btn-primary btn-sm" href="<?php echo url('admin/discord/connexion') ?>"><?php echo icon('fas fa-plug').' '.$this->lang('Connexion') ?></a>
	<?php if ($cle_bot): ?>
	<a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/discord/cle') ?>" data-confirm="<?php echo htmlspecialchars((string) $this->lang('Créer une nouvelle clé d’accès pour le bot ? L’ancienne sera révoquée : il faudra la remplacer sur la machine du bot.'), ENT_QUOTES) ?>"><?php echo icon('fas fa-key').' '.$this->lang('Nouvelle clé d’accès') ?></a>
	<?php else: ?>
	<a class="btn btn-outline-secondary btn-sm" href="<?php echo url('admin/discord/cle') ?>"><?php echo icon('fas fa-key').' '.$this->lang('Créer la clé d’accès') ?></a>
	<?php endif ?>
	<?php if (!empty($invitation)): ?>
	<a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars((string) $invitation) ?>" target="_blank" rel="noopener noreferrer" title="<?php echo htmlspecialchars((string) $this->lang('Ajoute le bot à un serveur Discord, avec les seules permissions dont il a besoin.'), ENT_QUOTES) ?>"><?php echo icon('fas fa-user-plus').' '.$this->lang('Inviter le bot') ?></a>
	<?php endif ?>
</div>
