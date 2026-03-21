<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
/**
 * Template Name: Content Template
 * Description: A template for displaying simple content.
 */


get_header();

get_template_part('parts/login-status');

if(!is_user_logged_in()) {
    // If the user is not logged in, include the login form
    get_template_part('parts/login-part');
} else {    
    echo '<h1>' . get_the_title() . '</h1>';    
    the_content();
    get_template_part('parts/item-shopping-list');
}




get_footer();

?>