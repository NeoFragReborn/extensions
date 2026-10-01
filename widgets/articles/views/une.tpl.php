<?php
/* Le billet à la une du Blog : couverture, catégorie, titre, extrait. */
$billet  = $billet ?? [];
$adresse = url('articles/'.$billet['article_id'].'/'.url_title((string) $billet['title']));
$image   = !empty($billet['image']) ? (string) NeoFrag()->model2('file', $billet['image'])->path() : '';
$extrait = !empty($billet['excerpt']) ? (string) $billet['excerpt'] : mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', strip_tags((string) render_content($billet['content'])))), 0, 140, '…');
?>
<article class="widget-blog-une">
	<a class="widget-blog-une-image<?php echo $image ? '' : ' vide' ?>" href="<?php echo $adresse ?>" tabindex="-1" aria-hidden="true"><?php if ($image): ?><img src="<?php echo $image ?>" alt="" loading="lazy" /><?php endif ?></a>
	<span class="widget-blog-pastille"><?php echo htmlspecialchars((string) $billet['category_title']) ?></span>
	<h3><a href="<?php echo $adresse ?>"><?php echo htmlspecialchars((string) $billet['title']) ?></a></h3>
	<p><?php echo htmlspecialchars($extrait) ?></p>
</article>
