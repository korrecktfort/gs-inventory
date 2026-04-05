<form method="post" class="loan-form">
    <?php wp_nonce_field('create_loan_action', 'create_loan_nonce'); ?>
    
    <?php get_template_part('template-parts/loans/form', 'loan-meta'); ?>
    <?php get_template_part('template-parts/loans/item', 'list'); ?>

    <div class="loan-form-submit">
        <button class="btn-submit ui-button" type="submit" name="create_loan_submit" value="1" disabled>
            Create Loan
        </button>

        <div class="loan-form-requirements" aria-live="polite">
            <p class="loan-form-requirements-title">Checklist:</p>
            <ul class="loan-form-missing-list"></ul>
        </div>
    </div>
</form>
