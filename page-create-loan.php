<?php
/* Template Name: Create Loan */

if (!is_user_logged_in()) {
    get_header();
    get_template_part('template-parts/ui/login-mask', null, [
        'title' => get_the_title() ?: 'Create Loan',
    ]);
    get_footer();
    return;
}

require_once get_template_directory() . '/inc/loans/loan-create-handler.php';

$result = gs_handle_create_loan();

if ( ! empty( $result['success'] ) && ! empty( $result['loan_id'] ) ) {
    wp_safe_redirect(
        add_query_arg(
            array(
                'loan_created' => 1,
            ),
            get_permalink( (int) $result['loan_id'] )
        )
    );
    exit;
}

get_header();

?>
<h1><?php the_title(); ?></h1>
<?php

if ( ! empty( $result['message'] ) ) : ?>
    <p><?php echo esc_html( $result['message'] ); ?></p>
<?php endif; ?>


<?php get_template_part('template-parts/loans/form', 'loan'); ?>

<?php
get_footer();
?>