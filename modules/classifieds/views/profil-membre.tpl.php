<?php
/*
 * L'onglet « Petites annonces » du profil public d'un membre (Classifieds::profil_membre(), chantier A, étape A2) :
 * le type et le prix comme sur la liste des annonces (Classifieds::type_label(), format_price()).
 */
?>
<ul class="nf-membre-liste">
	<?php foreach ($annonces as $annonce): ?>
		<li>
			<span>
				<?php echo \NF\Modules\Classifieds\Classifieds::type_label($annonce['ad_type']) ?>
				<a href="<?php echo url('classifieds/'.(int) $annonce['id'].'/'.url_title((string) $annonce['title'])) ?>"><?php echo nf_texte($annonce['title']) ?></a>
			</span>
			<small><?php echo \NF\Modules\Classifieds\Classifieds::format_price($annonce['price'], $this->module('classifieds')).' · '.nf_texte((string) $annonce['categorie']).' · '.timetostr('j M Y', strtotime((string) $annonce['created_at'])) ?></small>
		</li>
	<?php endforeach ?>
</ul>
