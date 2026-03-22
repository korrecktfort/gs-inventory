Hello

<form method="post" class="loan-form">
    <?php wp_nonce_field('create_loan_action', 'create_loan_nonce'); ?>

    <?php get_template_part('template-parts/loans/form', 'loan-meta'); ?>
    <?php get_template_part('template-parts/loans/item', 'list'); ?>

    <button type="submit" name="create_loan_submit" value="1">
        Loan anlegen
    </button>
</form>