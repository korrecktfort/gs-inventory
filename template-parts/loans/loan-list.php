<?php
$loans = get_posts([
    'post_type'      => 'loan',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<section class="loan-list" id="loan-list">
    <div class="loan-list-head">
        <h1 class="loan-list-heading">Loans</h1>

        <div class="loan-list-toolbar">
            <?php get_template_part('template-parts/ui/filter-input', null, [
                'filter_id' => 'loan-filter',
                'placeholder' => 'Search loans...',
                'target' => '#loan-list',
                'item_selector' => '.loan-list-row',
                'text_selector' => '.loan-list-title',
            ]); ?>
        </div>
    </div>

    <?php if (empty($loans)) : ?>
        <p class="loan-list-empty">No loans found.</p>
    <?php else : ?>
        <div class="loan-list-rows">
            <?php foreach ($loans as $loan) : ?>
                <?php get_template_part('template-parts/loans/loan-list', 'row', [
                    'loan' => $loan,
                ]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>