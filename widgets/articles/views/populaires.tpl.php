<?php
/* Les billets les plus lus du Blog, numérotés. */
$billets = $billets ?? [];
?>
<ol class="widget-blog-populaires">
	<?php foreach ($billets as $b): ?>
	<li><a href="<?php echo url('articles/'.$b['article_id'].'/'.url_title((string) $b['title'])) ?>"><?php echo htmlspecialchars((string) $b['title']) ?></a> <small><?php echo icon('far fa-eye').' '.(int) $b['views'] ?></small></li>
	<?php endforeach ?>
</ol>
