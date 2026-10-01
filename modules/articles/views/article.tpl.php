<?php
/*
 * La fiche d'un billet du Blog (2026-10-01). Trois mises en page, choisies dans les
 * réglages : « fusion » (sommaire à gauche, colonne de lecture, encart à droite — par défaut),
 * « sommaire » (sommaire à gauche, colonne de lecture), « centree » (une colonne, le sommaire replié
 * en tête). Sur téléphone, toutes se ramènent à une colonne et le sommaire se replie.
 */

use NF\Modules\Articles\Articles;

$fiche  = $fiche ?? 'fusion';
$toc    = $toc ?? '';
$autour = $autour ?? ['precedent' => NULL, 'suivant' => NULL, 'lies' => []];

$lien     = static fn (array $a): string => url('articles/'.$a['article_id'].'/'.url_title($a['title']));
$couv     = !empty($article['image']) ? (string) NeoFrag()->model2('file', $article['image'])->path() : '';
$auteur   = !empty($article['user_id']) ? $this->module('user')->model2('user', $article['user_id'])->avatar()->append_attr('class', 'blog-avatar').' '.$this->user->link($article['user_id'], $article['username']) : '';
$adresse  = absolute_url('articles/'.$article['article_id'].'/'.url_title($article['title']));
$categorie = url('articles/category/'.$article['category_id'].'/'.url_title($article['category_name']));
?>
<article class="blog-billet blog-fiche-<?php echo $fiche ?>">
	<header class="blog-entete<?php echo $couv ? ' blog-entete-image' : '' ?>">
		<?php if ($couv): ?><img class="blog-entete-fond" src="<?php echo $couv ?>" alt="" /><?php endif ?>
		<div class="blog-entete-texte">
			<a class="blog-pastille" href="<?php echo $categorie ?>"><?php echo htmlspecialchars((string) $article['category_title']) ?></a>
			<h1><?php echo htmlspecialchars((string) $article['title']) ?></h1>
			<div class="blog-meta">
				<?php if ($auteur): ?><span class="blog-auteur"><?php echo $auteur ?></span><?php endif ?>
				<span><?php echo timetostr('j M Y', $article['date']) ?></span>
				<span><?php echo $this->lang('%d min de lecture', (int) $read_time) ?></span>
				<span><?php echo icon('far fa-eye').' '.(int) $article['views'] ?></span>
			</div>
		</div>
	</header>

	<div class="blog-progression" aria-hidden="true"><span data-blog-progression></span></div>

	<div class="blog-lecture">
		<?php if (!empty($toc) && $fiche !== 'centree'): ?>
		<nav class="blog-sommaire" aria-label="<?php echo $this->lang('Sommaire') ?>"><?php echo $toc ?></nav>
		<?php endif ?>

		<div class="blog-texte">
			<?php if (!empty($toc)): ?>
			<details class="blog-sommaire-replie<?php echo $fiche === 'centree' ? ' toujours' : '' ?>"<?php echo $fiche === 'centree' ? ' open' : '' ?>>
				<summary><?php echo $this->lang('Sommaire') ?></summary>
				<?php echo $toc ?>
			</details>
			<?php endif ?>

			<div class="article-content blog-contenu" data-blog-contenu>
				<?php echo $content ?>
			</div>

			<?php if ($reactions = $this->module('reactions')): ?>
			<div class="mt-4"><?php echo $reactions->bar('article', (int) $article['article_id']) ?></div>
			<?php endif ?>

			<?php if (!empty($article['tags'])): ?>
			<div class="blog-tags mt-3">
				<?php foreach (array_filter(array_map('trim', explode(',', $article['tags']))) as $tag): ?>
				<a href="<?php echo url('articles/tag/'.url_title($tag)) ?>">#<?php echo htmlspecialchars($tag) ?></a>
				<?php endforeach ?>
			</div>
			<?php endif ?>

			<div class="mt-3"><?php echo share_buttons($adresse, $article['title']) ?></div>

			<?php if ($auteur): ?>
			<div class="blog-boite-auteur">
				<?php echo $this->module('user')->model2('user', $article['user_id'])->avatar()->append_attr('class', 'blog-avatar-grand') ?>
				<div>
					<small><?php echo $this->lang('Écrit par') ?></small>
					<strong><?php echo $this->user->link($article['user_id'], $article['username']) ?></strong>
				</div>
			</div>
			<?php endif ?>

			<?php if (($notifications = $this->module('notifications')) && ($btn = $notifications->follow_button('article', $article['article_id']))): ?>
			<div class="mt-3"><?php echo $btn ?></div>
			<?php endif ?>

			<?php if (!empty($autour['precedent']) || !empty($autour['suivant'])): ?>
			<nav class="blog-voisins" aria-label="<?php echo $this->lang('Billets voisins') ?>">
				<?php if (!empty($autour['precedent'])): ?>
				<a href="<?php echo $lien($autour['precedent']) ?>"><small><?php echo icon('fas fa-arrow-left').' '.$this->lang('Billet précédent') ?></small><?php echo htmlspecialchars((string) $autour['precedent']['title']) ?></a>
				<?php else: ?><span></span><?php endif ?>
				<?php if (!empty($autour['suivant'])): ?>
				<a class="suivant" href="<?php echo $lien($autour['suivant']) ?>"><small><?php echo $this->lang('Billet suivant').' '.icon('fas fa-arrow-right') ?></small><?php echo htmlspecialchars((string) $autour['suivant']['title']) ?></a>
				<?php endif ?>
			</nav>
			<?php endif ?>

			<?php if (!empty($autour['lies'])): ?>
			<section class="blog-lies">
				<h2><?php echo $this->lang('À lire aussi') ?></h2>
				<div>
					<?php foreach ($autour['lies'] as $lie): ?>
					<a href="<?php echo $lien($lie) ?>">
						<?php if (!empty($lie['image'])): ?><img src="<?php echo NeoFrag()->model2('file', $lie['image'])->path() ?>" alt="" loading="lazy" /><?php else: ?><span class="blog-couverture-vide"></span><?php endif ?>
						<span><?php echo htmlspecialchars((string) $lie['title']) ?></span>
					</a>
					<?php endforeach ?>
				</div>
			</section>
			<?php endif ?>

			<?php if (($comments = $this->module('comments')) && $comments->is_enabled()): ?>
			<section class="mt-4">
				<?php echo $comments('articles', $article['article_id']) ?>
			</section>
			<?php endif ?>
		</div>

		<?php if ($fiche === 'fusion'): ?>
		<aside class="blog-encart">
			<?php if ($auteur): ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Auteur') ?></h3>
				<span class="blog-auteur"><?php echo $auteur ?></span>
			</div>
			<?php endif ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Partager') ?></h3>
				<?php echo share_buttons($adresse, $article['title']) ?>
			</div>
			<?php if (!empty($autour['lies'])): ?>
			<div class="blog-boite">
				<h3><?php echo $this->lang('Dans la catégorie') ?></h3>
				<ul class="blog-categories">
					<?php foreach ($autour['lies'] as $lie): if ((int) $lie['category_id'] !== (int) $article['category_id']) continue; ?>
					<li><a href="<?php echo $lien($lie) ?>"><?php echo htmlspecialchars((string) $lie['title']) ?></a></li>
					<?php endforeach ?>
				</ul>
			</div>
			<?php endif ?>
		</aside>
		<?php endif ?>
	</div>
</article>
