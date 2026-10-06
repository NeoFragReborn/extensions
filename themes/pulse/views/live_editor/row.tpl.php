<?php
$accent = $this->config->pulse_theme_color ?: '#3d5566';
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('fas fa-square-full') ?> <?php echo $this->lang('Apparence de la ligne') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="row">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-default" data-label="<?php echo $this->lang('Fond transparent') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<defs>
						<pattern id="lePlDot" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="1.5" cy="1.5" r="1" fill="#cdd5dc"/></pattern>
					</defs>
					<rect width="200" height="90" rx="6" fill="url(#lePlDot)"/>
					<rect x="20"  y="20" width="50" height="50" rx="3" fill="#ffffff" stroke="#e1e6ea"/>
					<rect x="80"  y="20" width="50" height="50" rx="3" fill="#ffffff" stroke="#e1e6ea"/>
					<rect x="140" y="20" width="40" height="50" rx="3" fill="#ffffff" stroke="#e1e6ea"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond transparent') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-dark" data-label="<?php echo $this->lang('Fond sombre') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="6" fill="#161b22"/>
					<rect x="20"  y="20" width="50" height="50" rx="3" fill="#1f262f"/>
					<rect x="80"  y="20" width="50" height="50" rx="3" fill="#1f262f"/>
					<rect x="140" y="20" width="40" height="50" rx="3" fill="<?php echo $accent ?>"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond sombre') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-space" data-label="<?php echo $this->lang('Marge en haut') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="6" fill="#f3f5f7"/>
					<rect x="0" y="0" width="200" height="22" fill="#dde4e8"/>
					<rect x="20"  y="38" width="50" height="38" rx="3" fill="#ffffff" stroke="#cdd5dc"/>
					<rect x="80"  y="38" width="50" height="38" rx="3" fill="#ffffff" stroke="#cdd5dc"/>
					<rect x="140" y="38" width="40" height="38" rx="3" fill="#ffffff" stroke="#cdd5dc"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Marge en haut') ?></span>
		</button>
	</div>
</div>
