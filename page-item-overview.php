<?php
/* Template Name: Item Overview */

if (!is_user_logged_in()) {
	get_header();
	get_template_part('template-parts/ui/login-mask', null, [
		'title' => get_the_title() ?: 'Item Overview',
	]);
	get_footer();
	return;
}

get_header();
?>

<h1><?php the_title(); ?></h1>

<?php get_template_part('template-parts/items/item', 'list'); ?>

<?php
get_footer();
?>