<?php 
    /* Template Name: Loan Overview */

if (!is_user_logged_in()) {
    get_header();
    get_template_part('template-parts/ui/login-mask', null, [
        'title' => get_the_title() ?: 'Loan Overview',
    ]);
    get_footer();
    return;
}

get_header();

?>
<h1><?php the_title(); ?></h1>
<?php
get_template_part('template-parts/loans/loan', 'list');

get_footer();
?>