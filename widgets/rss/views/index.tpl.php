<?php
/**
 * Lecteur de flux : les derniers articles d'un site tiers.
 *
 * Tout ce qui s'affiche ici vient d'un site sur lequel nous n'avons aucune prise. Rien n'est donc
 * écrit sans `htmlspecialchars` — pas même le titre du flux, pas même le nom de l'auteur. Les liens
 * ont déjà été ramenés à `http`/`https` par le lecteur ; `rel="noopener nofollow"` et `target` les
 * ouvrent sans donner la main sur notre page ni transmettre notre réputation.
 */
?>
<div class="nf-rss">
	<?php if ($titre !== ''): ?>
	<div class="nf-rss-head">
		<i class="fas fa-rss" aria-hidden="true"></i>
		<a href="<?php echo htmlspecialchars($lien, ENT_QUOTES) ?>" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($titre) ?></a>
	</div>
	<?php endif ?>
	<ul class="nf-rss-list">
		<?php foreach ($articles as $nf_article): ?>
		<li class="nf-rss-item">
			<?php if ($nf_article['link'] !== ''): ?>
			<a class="nf-rss-title" href="<?php echo htmlspecialchars($nf_article['link'], ENT_QUOTES) ?>" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($nf_article['title']) ?></a>
			<?php else: ?>
			<span class="nf-rss-title"><?php echo htmlspecialchars($nf_article['title']) ?></span>
			<?php endif ?>
			<?php if (($avec_date || $nf_article['author'] !== '') && ($nf_article['date'] || $nf_article['author'] !== '')): ?>
			<div class="nf-rss-meta">
				<?php if ($avec_date && $nf_article['date']): ?>
				<?php /* `time_span()` rend DEJA un element <time datetime="…"> complet. L'echapper
				         affichait ses balises en toutes lettres — « <time datetime="2026-09-11…"> »
				         lisible tel quel sous chaque titre — et le reenvelopper aurait imbrique deux
				         <time>. On l'emet donc tel quel : sa valeur vient de la bibliotheque de
				         dates, pas du flux distant. Vu le 2026-09-22 en photographiant le widget. */ ?>
				<?php echo time_span((int) $nf_article['date']) ?>
				<?php endif ?>
				<?php if ($nf_article['author'] !== ''): ?>
				<span class="nf-rss-author"><?php echo htmlspecialchars($nf_article['author']) ?></span>
				<?php endif ?>
			</div>
			<?php endif ?>
			<?php if ($avec_resume && $nf_article['summary'] !== ''): ?>
			<p class="nf-rss-summary"><?php echo htmlspecialchars($nf_article['summary']) ?></p>
			<?php endif ?>
		</li>
		<?php endforeach ?>
	</ul>
</div>
