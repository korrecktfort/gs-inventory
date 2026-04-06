<?php
if (!is_user_logged_in()) {
	get_header();
	get_template_part('template-parts/ui/login-mask', null, [
		'title' => get_the_title() ?: 'Item',
	]);
	get_footer();
	return;
}

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
						'show_title' => false,
						'show_taxonomies' => true,
						'show_data_table' => true,
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
