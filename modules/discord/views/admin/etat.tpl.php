<?php
/* L'état du bot : en ligne, en pause, en connexion ou hors ligne ; l'interrupteur et le redémarrage. */
$reglages = $reglages ?? [];
$vie      = $vie ?? [];
$age      = $age ?? NULL;
$statut   = $statut ?? 'hors_ligne';
$liens    = $liens ?? [];
$guilde   = (array) ($vie['guild'] ?? []);
$intents  = (array) ($vie['intents'] ?? []);
?>
<p class="fs-5 mb-2">
	<?php if ($statut === 'en_ligne'): ?>
	<span class="badge text-bg-success"><?php echo icon('fas fa-circle').' '.$this->lang('En ligne') ?></span>
	<?php elseif ($statut === 'pause'): ?>
	<span class="badge text-bg-warning"><?php echo icon('fas fa-pause').' '.$this->lang('En pause') ?></span>
	<?php elseif ($statut === 'connexion'): ?>
	<span class="badge text-bg-info"><?php echo icon('fas fa-sync-alt').' '.$this->lang('Connexion à Discord…') ?></span>
	<?php else: ?>
	<span class="badge text-bg-secondary"><?php echo icon('far fa-circle').' '.$this->lang('Hors ligne') ?></span>
	<?php endif ?>
	<small class="text-muted"><?php echo !empty($reglages['running']) ? $this->lang('Interrupteur : en marche') : $this->lang('Interrupteur : en pause') ?></small>
</p>
<ul class="list-unstyled mb-3 small">
	<li><?php echo $this->lang('Dernier signe de vie : %s', $age === NULL ? $this->lang('jamais') : $this->lang('il y a %d s', $age)) ?></li>
	<?php if (!empty($vie['version'])): ?><li><?php echo $this->lang('Version du bot : %s', htmlspecialchars((string) $vie['version'])) ?></li><?php endif ?>
	<?php if (!empty($guilde['name'])): ?><li><?php echo $this->lang('Serveur : %s', htmlspecialchars((string) $guilde['name'])) ?></li><?php endif ?>
</ul>
<?php if ($statut === 'en_ligne' && (empty($intents['members']) || empty($intents['content']))): ?>
<div class="alert alert-warning small"><?php echo $this->lang('Le bot n’a pas accès à tout : activez « Server Members Intent » et « Message Content Intent » dans l’onglet « Bot » du portail des développeurs Discord, puis redémarrez-le.') ?></div>
<?php endif ?>
<div class="d-flex flex-wrap gap-2">
	<?php if (!empty($reglages['running'])): ?>
	<a class="btn btn-outline-warning btn-sm" href="<?php echo $liens['pause'] ?? '#' ?>"><?php echo icon('fas fa-pause').' '.$this->lang('Mettre en pause') ?></a>
	<?php else: ?>
	<a class="btn btn-success btn-sm" href="<?php echo $liens['marche'] ?? '#' ?>"><?php echo icon('fas fa-play').' '.$this->lang('Mettre en marche') ?></a>
	<?php endif ?>
	<a class="btn btn-outline-primary btn-sm" href="<?php echo $liens['redemarrer'] ?? '#' ?>"><?php echo icon('fas fa-redo').' '.$this->lang('Redémarrer') ?></a>
	<a class="btn btn-outline-secondary btn-sm" href="<?php echo $liens['resync'] ?? '#' ?>" title="<?php echo htmlspecialchars((string) $this->lang('Remettre tout d’accord : rôles et pseudos, et ce qui s’est écrit sur Discord pendant une absence du bot.'), ENT_QUOTES) ?>"><?php echo icon('fas fa-sync').' '.$this->lang('Resynchroniser') ?></a>
</div>
