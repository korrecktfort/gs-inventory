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
            <div class="item-list-toolbar-row item-list-toolbar-search-row">
                <div class="item-list-toolbar-section item-list-toolbar-search">
                    <?php get_template_part('template-parts/ui/filter-input', null, [
                        'filter_id' => 'item-filter',
                        'placeholder' => 'Search items...',
                        'target' => '#item-list-overview',
                        'item_selector' => '.item-overview-row',
                        'text_selector' => '.item-preview-title',
                    ]); ?>
                </div>

                <div class="item-list-toolbar-section item-list-toolbar-tags">
                    <?php get_template_part('template-parts/ui/filter-tags', null, [
                        'target' => '#item-list-overview',
                        'item_selector' => '.item-overview-row',
                        'tags_attribute' => 'data-item-tags',
                        'taxonomy' => 'item_tag',
                    ]); ?>
                </div>
            </div>

            <div id="filter-tags-selected" class="item-list-toolbar-tags-selected">
                <!-- selected tags appear here -->
            </div>
        </div>

        <?php if (empty($items)) : ?>
            <p class="item-list-empty">No items found.</p>
        <?php else : ?>
            <div class="item-list-scroll">
                <div class="item-overview-rows">
                    <?php foreach ($items as $item) : ?>
                        <?php
                        $tag_ids = get_field('tags', $item->ID);
                        $tag_ids = is_array($tag_ids) ? $tag_ids : [];
                        ?>
                        <div class="item-overview-row" data-item-tags="<?php echo esc_attr(wp_json_encode($tag_ids)); ?>">
                            <?php get_template_part('template-parts/items/item', 'preview', [
                                'item_id' => $item->ID,
                                'title_tag' => 'h2',
                                'title_url' => get_permalink($item->ID),
                                'description_source' => 'excerpt',
                                'show_image' => false,
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