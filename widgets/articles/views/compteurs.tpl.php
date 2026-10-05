<?php
/* Une liste de liens comptés : les catégories ou les mois d'archives du Blog. */
$lignes = $lignes ?? [];
?>
<ul class="widget-blog-compteurs">
	<?php foreach ($lignes as $l): ?>
	<li><a href="<?php echo url($l['adresse']) ?>"><?php echo nf_texte($l['titre']) ?></a> <span><?php echo (int) $l['total'] ?></span></li>
	<?php endforeach ?>
</ul>
