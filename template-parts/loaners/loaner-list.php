<?php
$loaners = get_posts([
    'post_type'      => 'loaner',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);
?>

<section class="loaner-overview-list" id="loaner-list-overview">
    <div class="loaner-overview-panel">
        <div class="loaner-overview-toolbar">
            <?php get_template_part('template-parts/ui/filter-input', null, [
                'filter_id' => 'loaner-filter',
                'placeholder' => 'Search loaners...',
                'target' => '#loaner-list-overview',
                'item_selector' => '.loaner-overview-row',
                'text_selector' => '.loaner-overview-title',
            ]); ?>
        </div>

        <?php if (empty($loaners)) : ?>
            <p class="loaner-overview-empty">No loaners found.</p>
        <?php else : ?>
            <div class="loaner-overview-scroll">
                <div class="loaner-overview-rows">
                    <?php foreach ($loaners as $loaner) : ?>
                        <?php get_template_part('template-parts/loaners/loaner-list', 'row', [
                            'loaner' => $loaner,
                        ]); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
