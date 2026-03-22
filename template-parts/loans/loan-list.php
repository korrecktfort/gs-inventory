<?php
$loans = get_posts([
    'post_type'      => 'loan',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);
?>

<?php get_template_part('template-parts/ui/filter-input', null, [
    'filter_id' => 'loan-filter',
    'placeholder' => 'Search loans...',
    'target' => '#loan-list',
    'item_selector' => '.loan-list-row',
    'text_selector' => '.loan-list-title',
]); ?>

<section class="loan-list" id="loan-list">
    <h1>Loans</h1>

    <?php if (empty($loans)) : ?>
        <p>No loans found.</p>
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