<?php
// Le vert par défaut de Blockcraft 1.x vaut celui de la 2.0 (cf. css/style.css).
$accent = in_array(strtolower((string) $this->config->blockcraft_theme_color), ['', '#6aa84f'], TRUE) ? '#3c7a27' : $this->config->blockcraft_theme_color;
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('far fa-square') ?> <?php echo $this->lang('Apparence du widget') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="widget">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-default" data-label="<?php echo $this->lang('Widget classique') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="0" fill="#eef3e6"/>
					<rect x="20" y="16" width="160" height="78" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="32" y="28" width="80"  height="8" rx="2" fill="#2e2a25"/>
					<rect x="32" y="46" width="136" height="5" rx="2" fill="#c4ccc2"/>
					<rect x="32" y="58" width="120" height="5" rx="2" fill="#c4ccc2"/>
					<rect x="32" y="70" width="100" height="5" rx="2" fill="#c4ccc2"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Widget classique') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-header" data-label="<?php echo $this->lang('Titre coloré') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="0" fill="#eef3e6"/>
					<rect x="20" y="16" width="160" height="78" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="20" y="16" width="160" height="22" rx="0" fill="<?php echo $accent ?>"/>
					<rect x="20" y="32" width="160" height="6" fill="<?php echo $accent ?>"/>
					<rect x="32" y="22" width="80" height="8" rx="2" fill="#ffffff"/>
					<rect x="32" y="50" width="136" height="5" rx="2" fill="#c4ccc2"/>
					<rect x="32" y="62" width="120" height="5" rx="2" fill="#c4ccc2"/>
					<rect x="32" y="74" width="100" height="5" rx="2" fill="#c4ccc2"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Titre coloré') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="panel-color" data-label="<?php echo $this->lang('Widget coloré') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 110" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="110" rx="0" fill="#eef3e6"/>
					<rect x="20" y="16" width="160" height="78" rx="0" fill="<?php echo $accent ?>"/>
					<rect x="32" y="28" width="80"  height="8" rx="2" fill="#ffffff"/>
					<rect x="32" y="46" width="136" height="5" rx="2" fill="#ffffff" opacity="0.7"/>
					<rect x="32" y="58" width="120" height="5" rx="2" fill="#ffffff" opacity="0.7"/>
					<rect x="32" y="70" width="100" height="5" rx="2" fill="#ffffff" opacity="0.7"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Widget coloré') ?></span>
		</button>
	</div>
</div>
