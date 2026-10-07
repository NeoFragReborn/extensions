<?php
// Le vert par défaut de Blockcraft 1.x vaut celui de la 2.0 (cf. css/style.css).
$accent = in_array(strtolower((string) $this->config->blockcraft_theme_color), ['', '#6aa84f'], TRUE) ? '#3c7a27' : $this->config->blockcraft_theme_color;
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('fas fa-square-full') ?> <?php echo $this->lang('Apparence de la ligne') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="row">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-default" data-label="<?php echo $this->lang('Fond transparent') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<defs>
						<pattern id="leBcDot" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="1.5" cy="1.5" r="1" fill="#cdd6cc"/></pattern>
					</defs>
					<rect width="200" height="90" rx="0" fill="url(#leBcDot)"/>
					<rect x="20"  y="20" width="50" height="50" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="80"  y="20" width="50" height="50" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="140" y="20" width="40" height="50" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond transparent') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-dark" data-label="<?php echo $this->lang('Fond sombre') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="0" fill="#1f242b"/>
					<rect x="20"  y="20" width="50" height="50" rx="0" fill="#2a2f37"/>
					<rect x="80"  y="20" width="50" height="50" rx="0" fill="#2a2f37"/>
					<rect x="140" y="20" width="40" height="50" rx="0" fill="<?php echo $accent ?>"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond sombre') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-space" data-label="<?php echo $this->lang('Marge en haut') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="0" fill="#eef3e6"/>
					<rect x="0" y="0" width="200" height="22" fill="#dde6dc"/>
					<rect x="20"  y="38" width="50" height="38" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="80"  y="38" width="50" height="38" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
					<rect x="140" y="38" width="40" height="38" rx="0" fill="#ffffff" stroke="#2b3720" stroke-width="2"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Marge en haut') ?></span>
		</button>
	</div>
</div>
