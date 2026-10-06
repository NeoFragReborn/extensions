<?php
/*
 * Les apparences d'un widget dans Pulse : trois dalles. Les styles sont ceux de tous les thèmes (panel-default,
 * panel-header, panel-color) ; Pulse en fait une dalle claire, une dalle soleil (l'adhésion de la maquette) et une
 * dalle ardoise (le prochain rendez-vous).
 */
$ardoise = $this->config->pulse_theme_color ?: '#3d5566';
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('far fa-square') ?> <?php echo $this->lang('Apparence du widget') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="widget">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-default" data-label="<?php echo $this->lang('Dalle claire') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="6" fill="#e8e6e1"/>
					<rect x="20" y="14" width="160" height="82" rx="18" fill="#fbfaf7"/>
					<rect x="36" y="30" width="60"  height="7" rx="3" fill="#646b6f"/>
					<rect x="36" y="48" width="128" height="6" rx="3" fill="#c9ccc7"/>
					<rect x="36" y="62" width="110" height="6" rx="3" fill="#c9ccc7"/>
					<rect x="36" y="76" width="90"  height="6" rx="3" fill="#c9ccc7"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Dalle claire') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-header" data-label="<?php echo $this->lang('Dalle soleil') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="6" fill="#e8e6e1"/>
					<rect x="20" y="14" width="160" height="82" rx="18" fill="#e3a72a"/>
					<rect x="36" y="30" width="60"  height="7" rx="3" fill="#2a2208" opacity="0.7"/>
					<rect x="36" y="46" width="70"  height="16" rx="4" fill="#2a2208"/>
					<rect x="36" y="72" width="110" height="6" rx="3" fill="#2a2208" opacity="0.6"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Dalle soleil') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-color" data-label="<?php echo $this->lang('Dalle ardoise') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="6" fill="#e8e6e1"/>
					<rect x="20" y="14" width="160" height="82" rx="18" fill="<?php echo nf_texte($ardoise) ?>"/>
					<rect x="36" y="30" width="34"  height="38" rx="8" fill="#ffffff"/>
					<rect x="82" y="32" width="80"  height="8" rx="3" fill="#ffffff"/>
					<rect x="82" y="48" width="70"  height="6" rx="3" fill="#ffffff" opacity="0.7"/>
					<rect x="82" y="60" width="40"  height="10" rx="5" fill="#e3a72a"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Dalle ardoise') ?></span>
		</button>
	</div>
</div>
