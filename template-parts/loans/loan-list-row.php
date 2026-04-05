<?php
$loan = $args['loan'] ?? null;

if (!$loan) {
    return;
}

$loan_id = $loan->ID;
$permalink = get_permalink($loan_id);
$status = (string) get_field('status', $loan_id);
$row_class = 'loan-list-row loan-list-row--card';

if ($status === 'returned') {
    $row_class .= ' is-returned';
}
?>

<div class="<?php echo esc_attr($row_class); ?>">
    <?php get_template_part('template-parts/loans/loan', 'single-card', [
        'loan_id' => $loan_id,
        'show_return_form' => false,
        'items_expanded' => false,
        'title_tag' => 'h2',
        'title_url' => $permalink,
    ]); ?>
</div>