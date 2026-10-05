<?php
/*
 * La liste du Blog (2026-10-01). Trois mises en page, choisies dans les réglages du
 * module : « barre » (la une, les cartes et la barre latérale — par défaut), « magazine » (la une
 * et les cartes, pleine largeur), « lignes » (une colonne de billets en lignes). Sur la grille, le
 * visiteur peut passer aux lignes ; js/blog.js retient son choix.
 */

use NF\Modules\Articles\Articles;

$articles   = $articles ?? [];
$une       = $une ?? NULL;
$barre      = $barre ?? [];
$categorie  = $categorie ?? 0;
$mois       = $mois ?? '';
$liste      = $liste ?? 'barre';
$pagination = $pagination ?? '';

$lien     = static fn (array $a): string => url('articles/'.$a['article_id'].'/'.url_title($a['title']));
$couv     = static fn (array $a): string => !empty($a['image']) ? (string) NeoFrag()->model2('file', $a['image'])->path() : '';
$extrait  = static fn (array $a): string => nf_texte(!empty($a['excerpt']) ? (string) $a['excerpt'] : mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string) render_content($a['content'])))), 0, 180, '…'));
$comments = ($m = $this->module('comments')) && $m->is_enabled() ? $m : NULL;
$auteur   = function (array $a): string {
	if (empty($a['user_id']))
	{
		return '';
	}

	// Le nom mène à la page de l'auteur dans le Blog : ses autres billets.
	return '<span class="blog-auteur">'.$this->module('user')->model2('user', $a['user_id'])->avatar()->append_attr('class', 'blog-avatar').' <a href="'.url('articles/auteur/'.(int) $a['user_id'].'/'.url_title((string) $a['username'])).'">'.nf_texte($a['username']).'</a></span>';
};

$carte = function (array $a) use ($lien, $couv, $extrait, $comments, $auteur): string {
	$html  = '<article class="blog-carte">';
	$html .= ($image = $couv($a)) ? '<a class="blog-couverture" href="'.$lien($a).'"><img src="'.$image.'" alt="" loading="lazy" /></a>' : '<a class="blog-couverture blog-couverture-vide" href="'.$lien($a).'" aria-hidden="true" tabindex="-1"></a>';
	$html .= '<div class="blog-carte-corps">';
	$html .= '<a class="blog-pastille" href="'.url('articles/category/'.$a['category_id'].'/'.url_title($a['category_name'])).'">'.nf_texte($a['category_title']).'</a>';
	$html .= '<h2 class="blog-carte-titre"><a href="'.$lien($a).'">'.nf_texte($a['title']).'</a></h2>';
	$html .= '<p class="blog-extrait">'.$extrait($a).'</p>';
	$html .= '<div class="blog-meta">'.$auteur($a).'<span>'.timetostr('j M Y', $a['date']).'</span><span>'.$this->lang('%d min de lecture', Articles::read_time_minutes((string) $a['content'])).'</span>';
	$html .= $comments ? '<span>'.$comments->link('articles', $a['article_id'], 'articles/'.$a['article_id'].'/'.url_title($a['title'])).'</span>' : '';
	$html .= '</div></div></article>';

	return $html;
};
?>
<div class="blog blog-liste-<?php echo $liste ?>">
	<?php if (!empty($une)): ?>
	<article class="blog-une">
		<?php if ($image = $couv($une)): ?>
		<a class="blog-une-image" href="<?php echo $lien($une) ?>"><img src="<?php echo $image ?>" alt="" /></a>
		<?php endif ?>
		<div class="blog-une-corps">
			<span class="blog-une-mention"><?php echo icon('fas fa-star').' '.$this->lang('À la une') ?></span>
			<a class="blog-pastille" href="<?php echo url('articles/category/'.$une['category_id'].'/'.url_title($une['category_name'])) ?>"><?php echo nf_texte($une['category_title']) ?></a>
			<h2 class="blog-une-titre"><a href="<?php echo $lien($une) ?>"><?php echo nf_texte($une['title']) ?></a></h2>
			<p class="blog-extrait"><?php echo $extrait($une) ?></p>
			<div class="blog-meta"><?php echo $auteur($une) ?><span><?php echo timetostr('j M Y', $une['date']) ?></span><span><?php echo $this->lang('%d min de lecture', Articles::read_time_minutes((string) $une['content'])) ?></span></div>
		</div>
	</article>
	<?php endif ?>

	<div class="blog-corps">
		<div class="blog-principal">
			<div class="blog-outils">
				<?php if (!empty($barre['categories'])): ?>
				<nav class="blog-filtres" aria-label="<?php echo $this->lang('Catégories') ?>">
					<a class="<?php echo !$categorie ? 'actif' : '' ?>" href="<?php echo url('articles') ?>"><?php echo $this->lang('Tout') ?></a>
					<?php foreach ($barre['categories'] as $c): ?>
					<a class="<?php echo $categorie === (int) $c['category_id'] ? 'actif' : '' ?>" href="<?php echo url('articles/category/'.$c['category_id'].'/'.url_title($c['name'])) ?>"><?php echo nf_texte($c['title']) ?></a>
					<?php endforeach ?>
				</nav>
				<?php endif ?>
				<?php if ($liste !== 'lignes'): ?>
				<div class="blog-vues" role="group" aria-label="<?php echo $this->lang('Affichage') ?>">
					<button type="button" data-blog-vue="grille" title="<?php echo $this->lang('Grille') ?>" aria-label="<?php echo $this->lang('Grille') ?>"><?php echo icon('fas fa-th-large') ?></button>
					<button type="button" data-blog-vue="lignes" title="<?php echo $this->lang('Lignes') ?>" aria-label="<?php echo $this->lang('Lignes') ?>"><?php echo icon('fas fa-list') ?></button>
				</div>
				<?php endif ?>
			</div>

			<?php if (empty($articles) && empty($une)): ?>
			<div class="alert alert-info text-center"><?php echo $this->lang('Aucun billet publié pour le moment.') ?></div>
			<?php else: ?>
			<div class="blog-billets<?php echo $liste === 'lignes' ? ' blog-billets-lignes' : '' ?>" data-blog-billets>
				<?php foreach ($articles as $article) echo $carte($article) ?>
			</div>
			<?php endif ?>

			<?php if (!empty($pagination)): ?>
			<div class="blog-pagination"><?php echo $pagination ?></div>
			<?php endif ?>
		</div>

		<?php if ($liste === 'barre' && !empty($barre['categories'])): ?>
		<aside class="blog-barre">
			<div class="blog-boite">
				<h3><?php echo $this->lang('Rechercher') ?></h3>
				<form action="<?php echo url('search') ?>" method="get" class="blog-recherche">
					<input type="search" name="q" class="form-control" placeholder="<?php echo $this->lang('Un mot, un sujet…') ?>" aria-label="<?php echo $this->lang('Rechercher') ?>" />
				</form>
			</div>
			<?php if (!empty($barre['populaires'])): ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Les plus lus') ?></h3>
				<ol class="blog-populaires">
					<?php foreach ($barre['populaires'] as $p): ?>
					<li><a href="<?php echo $lien($p) ?>"><?php echo nf_texte($p['title']) ?></a></li>
					<?php endforeach ?>
				</ol>
			</div>
			<?php endif ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Catégories') ?></h3>
				<ul class="blog-categories">
					<?php foreach ($barre['categories'] as $c): ?>
					<li><a href="<?php echo url('articles/category/'.$c['category_id'].'/'.url_title($c['name'])) ?>"><?php echo nf_texte($c['title']) ?></a> <span><?php echo (int) $c['total'] ?></span></li>
					<?php endforeach ?>
				</ul>
			</div>
			<?php if (!empty($barre['tags'])): ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Tags') ?></h3>
				<div class="blog-tags">
					<?php foreach ($barre['tags'] as $tag => $n): ?>
					<a href="<?php echo url('articles/tag/'.url_title((string) $tag)) ?>">#<?php echo nf_texte($tag) ?></a>
					<?php endforeach ?>
				</div>
			</div>
			<?php endif ?>
			<?php if (!empty($barre['archives'])): ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Archives') ?></h3>
				<ul class="blog-categories">
					<?php foreach ($barre['archives'] as $cle => $n): ?>
					<li><a class="<?php echo $mois === $cle ? 'actif' : '' ?>" href="<?php echo url('articles/archives/'.str_replace('-', '/', (string) $cle)) ?>"><?php echo timetostr('F Y', $cle.'-01') ?></a> <span><?php echo (int) $n ?></span></li>
					<?php endforeach ?>
				</ul>
			</div>
			<?php endif ?>
		</aside>
		<?php endif ?>
	</div>
</div>
