<?php
/*
 * L'onglet « Blog » du profil public d'un membre (Articles::profil_membre(), chantier A, étape A2).
 */
?>
<ul class="nf-membre-liste">
	<?php foreach ($billets as $billet): ?>
		<li>
			<a href="<?php echo url('articles/'.(int) $billet['article_id'].'/'.url_title((string) $billet['title'])) ?>"><?php echo nf_texte($billet['title']) ?></a>
			<small><?php echo timetostr('j M Y', (int) $billet['date']) ?></small>
		</li>
	<?php endforeach ?>
</ul>
