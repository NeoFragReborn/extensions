<?php
/* Les derniers billets du Blog : vignette, titre, date. */
$billets = $billets ?? [];
?>
<ul class="widget-blog-billets">
	<?php foreach ($billets as $b): $adresse = url('articles/'.$b['article_id'].'/'.url_title((string) $b['title'])); $image = !empty($b['image']) ? (string) NeoFrag()->model2('file', $b['image'])->path() : ''; ?>
	<li>
		<a class="widget-blog-vignette<?php echo $image ? '' : ' vide' ?>" href="<?php echo $adresse ?>" tabindex="-1" aria-hidden="true"><?php if ($image): ?><img src="<?php echo $image ?>" alt="" loading="lazy" /><?php endif ?></a>
		<div>
			<a href="<?php echo $adresse ?>"><?php echo htmlspecialchars((string) $b['title']) ?></a>
			<small><?php echo timetostr('j M Y', $b['date']) ?></small>
		</div>
	</li>
	<?php endforeach ?>
</ul>
