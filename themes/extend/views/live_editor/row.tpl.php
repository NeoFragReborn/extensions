<?php
$accent = $this->config->extend_theme_color ?: '#236daf';
?>
<div class="nf-le-style-section">
	<h6 class="nf-le-style-grid-title"><?php echo icon('fas fa-square-full') ?> <?php echo $this->lang('Apparence de la ligne') ?></h6>
	<div class="nf-le-style-grid" data-nf-target="row">
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-default" data-label="<?php echo $this->lang('Fond transparent') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<defs>
						<pattern id="leExDot" width="6" height="6" patternUnits="userSpaceOnUse"><circle cx="1.5" cy="1.5" r="1" fill="#44545f"/></pattern>
					</defs>
					<rect width="200" height="90" rx="4" fill="url(#leExDot)"/>
					<rect x="20"  y="20" width="50" height="50" rx="2" fill="#1a2229" stroke="#2a363f"/>
					<rect x="80"  y="20" width="50" height="50" rx="2" fill="#1a2229" stroke="#2a363f"/>
					<rect x="140" y="20" width="40" height="50" rx="2" fill="#1a2229" stroke="#2a363f"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond transparent') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-dark" data-label="<?php echo $this->lang('Fond sombre') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="4" fill="#1a2229"/>
					<rect x="20"  y="20" width="50" height="50" rx="2" fill="#212b33"/>
					<rect x="80"  y="20" width="50" height="50" rx="2" fill="#212b33"/>
					<rect x="140" y="20" width="40" height="50" rx="2" fill="<?php echo $accent ?>"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Fond sombre') ?></span>
		</button>
		<button type="button" class="nf-le-style-card live-editor-overview" data-style="row-space" data-label="<?php echo $this->lang('Marge en haut') ?>">
			<span class="nf-le-style-thumb">
				<svg viewBox="0 0 200 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<rect width="200" height="90" rx="4" fill="#11171a"/>
					<rect x="0" y="0" width="200" height="22" fill="#212b33"/>
					<rect x="20"  y="38" width="50" height="38" rx="2" fill="#1a2229" stroke="#2a363f"/>
					<rect x="80"  y="38" width="50" height="38" rx="2" fill="#1a2229" stroke="#2a363f"/>
					<rect x="140" y="38" width="40" height="38" rx="2" fill="#1a2229" stroke="#2a363f"/>
				</svg>
			</span>
			<span class="nf-le-style-label"><?php echo $this->lang('Marge en haut') ?></span>
		</button>
	</div>
</div>
