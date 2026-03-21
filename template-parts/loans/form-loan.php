<?php
$items = get_posts([
    'post_type'      => 'item',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);
?>

Hello

<form method="post" class="loan-form">
    <?php wp_nonce_field('create_loan_action', 'create_loan_nonce'); ?>

    <?php get_template_part('template-parts/loans/form', 'loan-meta'); ?>
    <?php get_template_part('template-parts/loans/form', 'loan-items', ['items' => $items]); ?>

    <button type="submit" name="create_loan_submit" value="1">
        Loan anlegen
    </button>
</form>