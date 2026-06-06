<?php if (empty($articles)): ?>
<div class="alert alert-info text-center">
	<?php echo $this->lang('Aucun article publié pour le moment.') ?>
</div>
<?php else: ?>
<div class="articles-list">
	<?php foreach ($articles as $article): ?>
	<article class="card mb-3">
		<?php if (!empty($article['image'])): ?>
		<a href="<?php echo url('articles/'.$article['article_id'].'/'.url_title($article['title'])) ?>"><img class="card-img-top" src="<?php echo NeoFrag()->model2('file', $article['image'])->path() ?>" alt="" /></a>
		<?php endif ?>
		<div class="card-body">
			<h2 class="card-title h4">
				<a href="<?php echo url('articles/'.$article['article_id'].'/'.url_title($article['title'])) ?>"><?php echo htmlspecialchars($article['title']) ?></a>
			</h2>
			<div class="text-muted small mb-2">
				<i class="far fa-calendar"></i> <?php echo timetostr('j M Y', $article['date']) ?>
				&middot; <i class="far fa-folder"></i> <a href="<?php echo url('articles/category/'.$article['category_id'].'/'.url_title($article['category_name'])) ?>"><?php echo htmlspecialchars($article['category_title']) ?></a>
				<?php if ($article['user_id']): ?>
				&middot; <i class="far fa-user"></i> <?php echo $this->user->link($article['user_id'], $article['username']) ?>
				<?php endif ?>
				&middot; <i class="far fa-clock"></i> <?php echo \NF\Modules\Articles\Articles::read_time_minutes($article['content']) ?> min
				&middot; <i class="far fa-eye"></i> <?php echo (int)$article['views'] ?>
			</div>
			<?php if (!empty($article['excerpt'])): ?>
			<p class="card-text"><?php echo htmlspecialchars($article['excerpt']) ?></p>
			<?php endif ?>
			<a href="<?php echo url('articles/'.$article['article_id'].'/'.url_title($article['title'])) ?>" class="btn btn-sm btn-primary"><?php echo $this->lang('Lire la suite') ?> <i class="fas fa-arrow-right"></i></a>
		</div>
	</article>
	<?php endforeach ?>
</div>
<?php endif ?>
