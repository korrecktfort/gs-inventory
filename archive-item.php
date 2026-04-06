<?php
if (!is_user_logged_in()) {
	get_header();
	get_template_part('template-parts/ui/login-mask', null, [
		'title' => 'All Items',
	]);
	get_footer();
	return;
}

get_header();
?>

<h1>All Items</h1>

<?php get_template_part('template-parts/items/item', 'list'); ?>

<?php
get_footer();
