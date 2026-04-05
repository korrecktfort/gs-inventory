<?php
$items = get_posts([
    'post_type' => 'item',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
]);
?>

<section class="item-list" id="item-list-overview">
    <div class="item-list-panel">
        <div class="item-list-toolbar">
            <?php get_template_part('template-parts/ui/filter-input', null, [
                'filter_id' => 'item-filter',
                'placeholder' => 'Search items...',
                'target' => '#item-list-overview',
                'item_selector' => '.item-overview-row',
                'text_selector' => '.item-preview-title',
            ]); ?>
        </div>

        <?php if (empty($items)) : ?>
            <p class="item-list-empty">No items found.</p>
        <?php else : ?>
            <div class="item-list-scroll">
                <div class="item-overview-rows">
                    <?php foreach ($items as $item) : ?>
                        <div class="item-overview-row">
                            <?php get_template_part('template-parts/items/item', 'preview', [
                                'item_id' => $item->ID,
                                'title_tag' => 'h2',
                                'title_url' => get_permalink($item->ID),
                                'description_source' => 'excerpt',
                                'root_class' => 'item-preview--list',
                            ]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>