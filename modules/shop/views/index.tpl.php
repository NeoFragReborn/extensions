<div class="shop-page">
	<div class="shop-head">
		<h2 class="mb-0"><?php echo icon('fas fa-store').' '.$this->lang('Boutique') ?></h2>
		<?php if ($logged): ?>
			<span class="shop-balance"><?php echo icon('fas fa-coins') ?> <strong data-shop-balance><?php echo (int)$balance ?></strong> <?php echo $this->lang('points') ?></span>
		<?php endif ?>
	</div>

	<div class="row">
		<?php foreach ($items as $it): $id = (int)$it['id']; $price = (int)$it['price']; $is_owned = !empty($owned[$id]); ?>
			<div class="col-sm-6 col-md-4 mb-3">
				<div class="card shop-item h-100" data-shop-item="<?php echo $id ?>">
					<div class="card-body text-center">
						<div class="shop-item-icon"><?php echo icon($it['icon'] ?: 'fas fa-gift') ?></div>
						<h5 class="card-title"><?php echo htmlspecialchars($it['title']) ?></h5>
						<?php if (!empty($it['description'])): ?><p class="card-text text-muted"><?php echo htmlspecialchars((string)$it['description']) ?></p><?php endif ?>
						<div class="shop-item-price"><?php echo icon('fas fa-coins').' '.$price ?></div>
					</div>
					<div class="card-footer text-center">
						<?php if (!$logged): ?>
							<a href="<?php echo url('user') ?>" class="btn btn-sm btn-outline-secondary"><?php echo $this->lang('Connexion requise') ?></a>
						<?php elseif ($is_owned): ?>
							<span class="btn btn-sm btn-success disabled"><?php echo icon('fas fa-check').' '.$this->lang('Possédé') ?></span>
						<?php elseif ($balance < $price): ?>
							<span class="btn btn-sm btn-outline-secondary disabled"><?php echo $this->lang('Points insuffisants') ?></span>
						<?php else: ?>
							<button type="button" class="btn btn-sm btn-primary" data-shop-buy data-buy-url="<?php echo url('shop/ajax/buy/'.$id) ?>"><?php echo icon('fas fa-shopping-cart').' '.$this->lang('Acheter') ?></button>
						<?php endif ?>
					</div>
				</div>
			</div>
		<?php endforeach ?>

		<?php if (!$items): ?>
			<div class="col-12"><div class="alert alert-info"><?php echo $this->lang('La boutique est vide pour le moment.') ?></div></div>
		<?php endif ?>
	</div>
</div>
