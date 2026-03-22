<?php 
    /* Template Name: Loan Overview */
    
    if(!is_user_logged_in()) {
        auth_redirect();
    }


require_once get_template_directory() . '/inc/loans/loan-queries.php';

get_header();

get_template_part('template-parts/loans/loan', 'list');

get_footer();
?>