<?php
/* Template Name: Create Loan */

if (!is_user_logged_in()) {
    auth_redirect();
}

require_once get_template_directory() . '/inc/loans/loan-create-handler.php';

$result = gs_handle_create_loan();

if (!empty($result['success']) && !empty($result['loan_id'])) {
    wp_redirect(add_query_arg([
        'loan_created' => 1,
        'loan_id' => $result['loan_id'],
    ], get_permalink()));
    exit;
}

get_header();

?>
<h1><?php the_title(); ?></h1>
<?php

if (!empty($_GET['loan_created']) && !empty($_GET['loan_id'])) {
    $loan_id = (int) $_GET['loan_id'];
    $loan_title = get_the_title($loan_id);

    echo '<p>Loan "' . esc_html($loan_title) . '" created successfully.</p>';
}

if (!empty($result['message'])) : ?>
    <p><?php echo esc_html($result['message']); ?></p>
<?php endif;?>


<?php get_template_part('template-parts/loans/form', 'loan'); ?>

<?php
get_footer();
?>