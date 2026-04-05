<?php
get_header();
?>

<main class="item-single-page">
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<article class="item-modal-dialog item-single-dialog">
				<header class="item-modal-head">
					<h1 class="item-modal-title"><?php the_title(); ?></h1>
				</header>

				<div class="item-modal-content">
					<?php get_template_part('template-parts/items/item', 'preview', [
						'item_id' => get_the_ID(),
						'description_source' => 'content',
						'show_title' => false,
						'root_class' => 'item-preview--single item-preview--modal',
					]); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p>No item found.</p>
	<?php endif; ?>
</main>

<?php
get_footer();
