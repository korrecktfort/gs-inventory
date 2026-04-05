<?php
$loans = get_posts([
    'post_type'      => 'loan',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

usort($loans, static function ($left, $right) {
    $left_status = (string) get_field('status', $left->ID);
    $right_status = (string) get_field('status', $right->ID);

    $left_returned = ($left_status === 'returned');
    $right_returned = ($right_status === 'returned');

    if ($left_returned !== $right_returned) {
        return $left_returned ? 1 : -1;
    }

    return strcmp((string) $right->post_date, (string) $left->post_date);
});
?>

<section class="loan-list" id="loan-list">
    <div class="loan-list-panel">
        <div class="loan-list-toolbar">
            <?php get_template_part('template-parts/ui/filter-input', null, [
                'filter_id' => 'loan-filter',
                'placeholder' => 'Search loans...',
                'target' => '#loan-list',
                'item_selector' => '.loan-list-row',
                'text_selector' => '.loan-single-title',
            ]); ?>
        </div>

        <?php if (empty($loans)) : ?>
            <p class="loan-list-empty">No loans found.</p>
        <?php else : ?>
            <div class="loan-list-scroll">
                <div class="loan-list-rows">
                    <?php foreach ($loans as $loan) : ?>
                        <?php get_template_part('template-parts/loans/loan-list', 'row', [
                            'loan' => $loan,
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>