<?php
/* Les tags du Blog, des plus employés aux moins employés. */
$tags = $tags ?? [];
?>
<div class="widget-blog-tags">
	<?php foreach ($tags as $tag => $n): ?>
	<a href="<?php echo url('articles/tag/'.url_title((string) $tag)) ?>" title="<?php echo $this->lang('%d billet|%d billets', (int) $n, (int) $n) ?>">#<?php echo nf_texte($tag) ?></a>
	<?php endforeach ?>
</div>
