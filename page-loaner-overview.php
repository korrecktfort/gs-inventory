<?php
/* Template Name: Loaner Overview */

if (!is_user_logged_in()) {
    auth_redirect();
}

get_header();
?>

<h1><?php the_title(); ?></h1>

<?php get_template_part('template-parts/loaners/loaner', 'list'); ?>

<?php
get_footer();
