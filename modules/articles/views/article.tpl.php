<article class="article-full">
	<header class="mb-4">
		<h1><?php echo htmlspecialchars($article['title']) ?></h1>
		<div class="text-muted">
			<i class="far fa-calendar"></i> <?php echo timetostr('j M Y', $article['date']) ?>
			&middot; <i class="far fa-folder"></i> <a href="<?php echo url('articles/category/'.$article['category_id'].'/'.url_title($article['category_name'])) ?>"><?php echo htmlspecialchars($article['category_title']) ?></a>
			<?php if ($article['user_id']): ?>
			&middot; <i class="far fa-user"></i> <?php echo $this->user->link($article['user_id'], $article['username']) ?>
			<?php endif ?>
			&middot; <i class="far fa-clock"></i> <?php echo (int)$read_time ?> <?php echo $this->lang('min de lecture') ?>
			&middot; <i class="far fa-eye"></i> <?php echo (int)$article['views'] ?>
		</div>
	</header>

	<?php if (!empty($article['image'])): ?>
	<img class="img-fluid rounded mb-4 w-100" src="<?php echo NeoFrag()->model2('file', $article['image'])->path() ?>" alt="<?php echo htmlspecialchars($article['title']) ?>" />
	<?php endif ?>

	<?php if (!empty($toc)): ?>
		<?php echo $toc ?>
	<?php endif ?>

	<div class="article-content">
		<?php echo $content ?>
	</div>

	<?php if ($reactions = $this->module('reactions')): ?>
	<div class="mt-3"><?php echo $reactions->bar('article', (int)$article['article_id']) ?></div>
	<?php endif ?>

	<div class="mt-3"><?php echo share_buttons(absolute_url('articles/'.$article['article_id'].'/'.url_title($article['title'])), $article['title']) ?></div>

	<?php if (!empty($article['tags'])): ?>
	<footer class="mt-4 pt-3 border-top">
		<div class="article-tags">
			<i class="fas fa-tags"></i>
			<?php foreach (array_filter(array_map('trim', explode(',', $article['tags']))) as $tag): ?>
				<a href="<?php echo url('articles/tag/'.url_title($tag)) ?>" class="badge text-bg-secondary me-1">#<?php echo htmlspecialchars($tag) ?></a>
			<?php endforeach ?>
		</div>
	</footer>
	<?php endif ?>

	<?php if (($notifications = $this->module('notifications')) && ($btn = $notifications->follow_button('article', $article['article_id']))): ?>
	<div class="mt-3"><?php echo $btn ?></div>
	<?php endif ?>

	<?php if (($comments = $this->module('comments')) && $comments->is_enabled()): ?>
	<section class="mt-4">
		<?php echo $comments('articles', $article['article_id']) ?>
	</section>
	<?php endif ?>
</article>
