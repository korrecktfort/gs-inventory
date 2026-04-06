<?php
if ( ! is_user_logged_in() ) {
	get_header();
	get_template_part(
		'template-parts/ui/login-mask',
		null,
		array(
			'title' => get_the_title() ?: 'Loan',
		)
	);
	get_footer();
	return;
}

require_once get_template_directory() . '/inc/loans/loan-return-handler.php';

$return_result = gs_handle_return_loan();

if ( ! empty( $return_result['success'] ) && ! empty( $return_result['loan_id'] ) ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'loan_returned' => 1,
			),
			get_permalink( $return_result['loan_id'] )
		)
	);
	exit;
}
?>


<?php
$loan_id = get_the_ID();
?>

<?php get_header(); ?>

<main class="loan-single-page">
	<div class="loan-single-inner">

<?php $loan_created = (int) ( filter_input( INPUT_GET, 'loan_created', FILTER_VALIDATE_INT ) ?: 0 ); ?>
<?php if ( $loan_created === 1 ) : ?>
	<p class="loan-single-notice" role="status" aria-live="polite">
		Loan created successfully!
	</p>
<?php endif; ?>

<?php $loan_returned = (int) ( filter_input( INPUT_GET, 'loan_returned', FILTER_VALIDATE_INT ) ?: 0 ); ?>
<?php if ( $loan_returned === 1 ) : ?>
	<p class="loan-single-notice">Loan returned successfully.</p>
<?php endif; ?>

<?php
get_template_part(
	'template-parts/loans/loan',
	'single-card',
	array(
		'loan_id'          => $loan_id,
		'show_return_form' => true,
		'items_expanded'   => true,
		'title_tag'        => 'h1',
	)
);
?>
</div>
</main>

<?php get_template_part( 'template-parts/items/item-info', 'modal' ); ?>

<?php get_footer(); ?>
