<?php
/*
 * La page d'une série du Blog (2026-10-01) : sa présentation, puis ses parties dans
 * l'ordre, chacune avec son rang.
 */

use NF\Modules\Articles\Articles;

$serie   = $serie ?? ['title' => '', 'description' => ''];
$parties = $parties ?? [];

$lien    = static fn (array $a): string => url('articles/'.$a['article_id'].'/'.url_title($a['title']));
$extrait = static fn (array $a): string => htmlspecialchars(!empty($a['excerpt']) ? (string) $a['excerpt'] : mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string) render_content($a['content'])))), 0, 180, '…'));
?>
<div class="blog blog-page-serie">
	<header class="blog-page-entete">
		<div>
			<small class="blog-une-mention"><?php echo icon('fas fa-layer-group').' '.$this->lang('Série') ?></small>
			<h1><?php echo htmlspecialchars((string) $serie['title']) ?></h1>
			<?php if (!empty($serie['description'])): ?><p><?php echo nl2br(htmlspecialchars((string) $serie['description'])) ?></p><?php endif ?>
			<p><?php echo $this->lang('%d partie|%d parties', count($parties), count($parties)) ?></p>
		</div>
	</header>

	<ol class="blog-serie-parties">
		<?php foreach ($parties as $i => $partie): ?>
		<li>
			<span class="blog-serie-rang"><?php echo $i + 1 ?></span>
			<div>
				<h2><a href="<?php echo $lien($partie) ?>"><?php echo htmlspecialchars((string) $partie['title']) ?></a></h2>
				<p class="blog-extrait"><?php echo $extrait($partie) ?></p>
				<div class="blog-meta"><span><?php echo timetostr('j M Y', $partie['date']) ?></span><span><?php echo $this->lang('%d min de lecture', Articles::read_time_minutes((string) $partie['content'])) ?></span></div>
			</div>
		</li>
		<?php endforeach ?>
	</ol>
</div>
