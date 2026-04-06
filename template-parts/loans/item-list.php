<?php
$items = get_posts([
    'post_type'      => 'item',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    ]);
    ?>

<section class="item-list" id="loan-item-list">
    <div class="loan-title-field">
        <label for="item-filter" class="ui-label">Select Items</label>
    </div>   
    
<?php 
require_once get_template_directory() . '/inc/loans/loan-queries.php'; 
$loanedQuantities = gs_get_loaned_quantities_map();
?>

<div class="item-list-panel">
    <div class="item-list-toolbar">
        <div class="item-list-toolbar-row item-list-toolbar-search-row">
            <div class="item-list-toolbar-section item-list-toolbar-search">
                <?php get_template_part('template-parts/ui/filter-input', null, [
                    'filter_id' => 'item-filter',
                    'placeholder' => 'Filter items...',
                    'target' => '#loan-item-list',
                    'item_selector' => '.item-row',
                    'text_selector' => '.item-info-trigger',
                ]); ?>
            </div>

            <div class="item-list-toolbar-section item-list-toolbar-tags">
                <?php get_template_part('template-parts/ui/filter-tags', null, [
                    'target' => '#loan-item-list',
                    'item_selector' => '.item-row',
                    'tags_attribute' => 'data-item-filter-terms',
                    'taxonomies' => ['item_tag', 'storage_locations'],
                    'custom_terms' => [
                        [
                            'value' => 'meta:available',
                            'name' => 'Available',
                        ],
                    ],
                    'default_selected_terms' => ['meta:available'],
                ]); ?>
            </div>
        </div>

        <div id="filter-tags-selected" class="item-list-toolbar-tags-selected">
            <!-- selected tags appear here -->
        </div>
    </div>

    <div class="item-list-scroll">
<?php foreach ($items as $item) : ?>
    
    <?php 
        $id = $item->ID;
        $id_escaped = esc_attr($id);
        $name = get_the_title($id);
        $stock_total = (int) get_field('stock_total', $id); 
        $loaned = $loanedQuantities[$id] ?? 0;
        $available = max(0, $stock_total - $loaned);
        $class_disabled = ($available <= 0) ? 'disabled' : '';
        
        $filter_terms = [];
        $terms = wp_get_object_terms($id, ['item_tag', 'storage_locations']);

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
        $tags_field_value = get_field('tags', $id);
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

        // Fallback: include ACF storage field values even when term relationships are not synced.
        $storage_field_value = get_field('storage-location', $id);
        if ($storage_field_value === null || $storage_field_value === false || $storage_field_value === '') {
            $storage_field_value = get_field('storage_location', $id);
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

        if ($available > 0) {
            $filter_terms[] = 'meta:available';
        }

        $filter_terms = array_values(array_unique($filter_terms));
        $filter_terms_json = wp_json_encode($filter_terms);
    ?> 


<div class="item-row <?php echo esc_attr($class_disabled); ?>" data-available="<?php echo esc_attr($available); ?>" data-stock-total="<?php echo esc_attr($stock_total); ?>" data-item-filter-terms="<?php echo esc_attr($filter_terms_json); ?>">
    
    <!-- Display Name -->
    <div class="item-name column"> 
        <?php get_template_part( 'template-parts/items/item-info', 'trigger', ['item_id' => $id] ); ?>
    </div>

    <div class="item-row-controls">
        <!-- Display Availability -->
        <div class="item-availability column">        
            <p class="item-availability-value"><?php echo esc_html($available) . "/" . esc_html($stock_total); ?></p>
        </div>

        <!-- Assign Quantity To Loan -->
        <div class="item-quantity column">
            <button type="button" class="qty-btn qty-reset ui-button">--</button>
            <button type="button" class="qty-btn qty-minus ui-button">-</button>
            <input 
            class = "qty-input ui-input"
            type="number"
            name="loan_items[<?php echo $id_escaped; ?>][quantity]"
            min="0"
            max="<?php echo esc_attr($available); ?>"
            value="0"        
            >
            <button type="button" class="qty-btn qty-plus ui-button">+</button>
            <button type="button" class="qty-btn qty-max ui-button">++</button>
        </div>
    </div>

</div>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="loan-summary">    
    <div class="loan-summary-list loan-summary-list--compact">
        <!-- Dynamically populated list of selected items will go here -->
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>
        