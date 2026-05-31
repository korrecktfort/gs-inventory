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
                        'text_selector' => '.item-info-trigger-label',
                    ]); ?>
                </div>

                <div class="item-list-toolbar-section item-list-toolbar-tags">
                    <?php get_template_part('template-parts/ui/filter-tags', null, [
                        'target' => '#item-list-overview',
                        'item_selector' => '.item-overview-row',
                        'tags_attribute' => 'data-item-filter-terms',
                        'taxonomies' => ['item_tag', 'storage_locations'],
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
                        $filter_terms = [];
                        $tag_ids = array_values(array_filter(array_map('intval', (array) get_field('tags', $item->ID))));

                        foreach ($tag_ids as $tag_id) {
                            $filter_terms[] = 'item_tag:' . $tag_id;
                        }

                        $storage_ids = array_values(array_filter(array_map('intval', (array) get_field('storage_location', $item->ID))));

                        foreach ($storage_ids as $storage_id) {
                            $filter_terms[] = 'storage_locations:' . $storage_id;
                        }

                        $filter_terms = array_values(array_unique($filter_terms));
                        ?>
                        <div class="item-overview-row" data-item-filter-terms="<?php echo esc_attr(wp_json_encode($filter_terms)); ?>">
                            <?php get_template_part('template-parts/items/item', 'preview', [
                                'item_id' => $item->ID,
                                'title_tag' => 'h2',
                                'title_modal_trigger' => true,
                                'description_source' => 'excerpt',
                                'show_image' => false,
                                'root_class' => 'item-preview--list',
                            ]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="item-list-empty item-list-empty--filtered" id="item-overview-filter-empty" hidden>No items match current filters.</p>
        <?php endif; ?>
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>
