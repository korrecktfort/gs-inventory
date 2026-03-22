<?php
$loan = $args['loan'] ?? null;

if (!$loan) {
    return;
}

$loan_id = $loan->ID;
$title = get_the_title($loan_id);
$permalink = get_permalink($loan_id);

$status = get_field('status', $loan_id);
$start_date = get_field('start_date', $loan_id);
$due_date = get_field('due_date', $loan_id);
?>

<article class="loan-list-row">
    <h2 class="loan-list-title">
        <a href="<?php echo esc_url($permalink); ?>">
            <?php echo esc_html($title); ?>
        </a>
    </h2>

    <div class="loan-list-meta">
        <div>
            <strong>Status:</strong>
            <?php echo esc_html($status ?: '—'); ?>
        </div>

        <div>
            <strong>Start Date:</strong>
            <?php echo esc_html($start_date ?: '—'); ?>
        </div>

        <div>
            <strong>Return Date:</strong>
            <?php echo esc_html($due_date ?: '—'); ?>
        </div>
    </div>
</article>