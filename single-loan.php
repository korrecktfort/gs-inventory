<?php 
require_once get_template_directory() . '/inc/loans/loan-return-handler.php';

$return_result = gs_handle_return_loan();

if (!empty($return_result['success']) && !empty($return_result['loan_id'])) {
    wp_redirect(add_query_arg([
        'loan_returned' => 1,
    ], get_permalink($return_result['loan_id'])));
    exit;
}
?>


<?php 
$loan_id = get_the_ID();
$loan_items = get_posts([
    'post_type'      => 'loan_item',    
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => 'related_loan',
            'value'   => $loan_id,
            'compare' => '=',
        ],
    ],
]); 

$startDate = get_field('start_date', $loan_id);
$dueDate = get_field('due_date', $loan_id);
?>

<?php get_header()?>

<?php if (!empty($_GET['loan_returned'])) : ?>
    <p>Loan returned successfully.</p>
<?php endif; ?>

<h3> Name: <?php echo esc_html(get_the_title()); ?> </h3>

<input type="text" value="<?php echo esc_attr($startDate); ?>" disabled>
<input type="text" value="<?php echo esc_attr($dueDate); ?>" disabled>

<?php
$loan_id = get_the_ID();
$status = get_field('status', $loan_id);
?>

<div>
    Status: <?php echo esc_html($status); ?>
</div>

<?php if ($status === 'active') : ?>
    <form method="post">
        <?php wp_nonce_field('return_loan_action', 'return_loan_nonce'); ?>
        <input type="hidden" name="loan_id" value="<?php echo esc_attr($loan_id); ?>">
        <button type="submit" name="return_loan_submit" value="1">
            Mark as returned
        </button>
    </form>
<?php endif; ?>

<h3> Items: </h3>
<?php if($loan_items) : ?> 
<?php foreach ($loan_items as $loan_item) : ?>
    <?php 
        $item_id = get_field('related_item', $loan_item->ID);
        $quantity = get_field('quantity', $loan_item->ID);
        $item_name = get_the_title($item_id);
    ?>
    <?php get_template_part('template-parts/items/item-info', 'trigger', ['item_id' => $item_id]); ?>
    <p> <?php echo esc_html($item_name) . ' : ' . esc_html($quantity); ?> </p>
<?php endforeach; ?>
<?php else : ?>
    <p>No items found for this loan.</p>
<?php endif; ?>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>

<?php get_footer()?>
