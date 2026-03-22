<?php
/* Template Name: Loans */

if (!is_user_logged_in()) {
    auth_redirect();
}

require_once get_template_directory(['items' => $items]) . '/template-parts/inc/loans/loan-create-handler.php';

$result = gs_handle_create_loan();

get_header();

if (!empty($result['message'])) : ?>
    <p><?php echo esc_html($result['message']); ?></p>
<?php endif;?>


<?php get_template_part('template-parts/loans/form', 'loan'); ?>

<?php
get_footer();
?>