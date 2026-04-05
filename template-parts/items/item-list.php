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
                        $terms = wp_get_object_terms($item->ID, ['item_tag', 'storage_locations']);

                        if (!is_wp_error($terms)) {
                            foreach ($terms as $term) {
                                $term_id = (int) ($term->term_id ?? 0);
                                $taxonomy_name = (string) ($term->taxonomy ?? '');

                                if ($term_id <= 0 || $taxonomy_name === '') {
                                    continue;
                                }

                                $filter_terms[] = $taxonomy_name . ':' . $term_id;
                            }
                        }

                        // Fallback: include ACF tags field values when term relationships are not synced.
                        $tags_field_value = get_field('tags', $item->ID);
                        $tag_ids = [];

                        if (is_numeric($tags_field_value)) {
                            $tag_ids[] = (int) $tags_field_value;
                        } elseif (is_object($tags_field_value) && isset($tags_field_value->term_id)) {
                            $tag_ids[] = (int) $tags_field_value->term_id;
                        } elseif (is_array($tags_field_value)) {
                            foreach ($tags_field_value as $tag_item) {
                                if (is_numeric($tag_item)) {
                                    $tag_ids[] = (int) $tag_item;
                                    continue;
                                }

                                if (is_object($tag_item) && isset($tag_item->term_id)) {
                                    $tag_ids[] = (int) $tag_item->term_id;
                                    continue;
                                }

                                if (is_array($tag_item) && isset($tag_item['term_id'])) {
                                    $tag_ids[] = (int) $tag_item['term_id'];
                                }
                            }
                        }

                        foreach ($tag_ids as $tag_id) {
                            if ($tag_id > 0) {
                                $filter_terms[] = 'item_tag:' . $tag_id;
                            }
                        }

                        // Fallback: include ACF storage field values when term relationships are not synced.
                        $storage_field_value = get_field('storage-location', $item->ID);
                        if ($storage_field_value === null || $storage_field_value === false || $storage_field_value === '') {
                            $storage_field_value = get_field('storage_location', $item->ID);
                        }

                        $storage_ids = [];

                        if (is_numeric($storage_field_value)) {
                            $storage_ids[] = (int) $storage_field_value;
                        } elseif (is_object($storage_field_value) && isset($storage_field_value->term_id)) {
                            $storage_ids[] = (int) $storage_field_value->term_id;
                        } elseif (is_array($storage_field_value)) {
                            foreach ($storage_field_value as $storage_item) {
                                if (is_numeric($storage_item)) {
                                    $storage_ids[] = (int) $storage_item;
                                    continue;
                                }

                                if (is_object($storage_item) && isset($storage_item->term_id)) {
                                    $storage_ids[] = (int) $storage_item->term_id;
                                    continue;
                                }

                                if (is_array($storage_item) && isset($storage_item['term_id'])) {
                                    $storage_ids[] = (int) $storage_item['term_id'];
                                }
                            }
                        }

                        foreach ($storage_ids as $storage_id) {
                            if ($storage_id > 0) {
                                $filter_terms[] = 'storage_locations:' . $storage_id;
                            }
                        }

                        $filter_terms = array_values(array_unique($filter_terms));
                        ?>
                        <div class="item-overview-row" data-item-filter-terms="<?php echo esc_attr(wp_json_encode($filter_terms)); ?>">
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
            <p class="item-list-empty item-list-empty--filtered" id="item-overview-filter-empty" hidden>No items match current filters.</p>
        <?php endif; ?>
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('item-list-overview');
    const rowsWrap = root ? root.querySelector('.item-overview-rows') : null;
    const emptyFiltered = document.getElementById('item-overview-filter-empty');

    if (!root || !rowsWrap || !emptyFiltered) {
        return;
    }

    function updateFilteredEmptyState() {
        const rows = rowsWrap.querySelectorAll('.item-overview-row');
        const visibleCount = Array.from(rows).filter((row) => row.style.display !== 'none' && !row.hidden).length;

        emptyFiltered.hidden = visibleCount !== 0;
    }

    root.addEventListener('gs:filter-updated', updateFilteredEmptyState);
    updateFilteredEmptyState();
});
</script>