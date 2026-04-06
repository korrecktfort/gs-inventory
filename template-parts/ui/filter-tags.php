<?php
/**
 * Tag Filter Component (Loaner Picker Pattern)
 * Searchable dropdown of taxonomy tags for filtering items
 * 
 * Args:
 *   - target: CSS selector for container of items to filter
 *   - item_selector: CSS selector for individual item rows
 *   - tags_attribute: data attribute name containing tag IDs (default: data-item-tags)
 *   - taxonomy: single taxonomy name (backward compatible)
 *   - taxonomies: taxonomy names array (preferred)
 */

$target = $args['target'] ?? '';
$item_selector = $args['item_selector'] ?? '';
$tags_attribute = $args['tags_attribute'] ?? 'data-item-filter-terms';
$custom_terms = $args['custom_terms'] ?? [];
$default_selected_terms = $args['default_selected_terms'] ?? [];

$taxonomies_arg = $args['taxonomies'] ?? ($args['taxonomy'] ?? 'item_tag');
$taxonomies = is_array($taxonomies_arg) ? $taxonomies_arg : [$taxonomies_arg];
$taxonomies = array_values(array_filter(array_unique(array_map('strval', $taxonomies)), function ($taxonomy_name) {
    return taxonomy_exists($taxonomy_name);
}));

if (empty($taxonomies)) {
    $taxonomies = ['item_tag'];
}

$taxonomy_labels = [];
foreach ($taxonomies as $taxonomy_name) {
    $taxonomy_object = get_taxonomy($taxonomy_name);
    $label = $taxonomy_name;

    if ($taxonomy_object && isset($taxonomy_object->labels->singular_name)) {
        $label = (string) $taxonomy_object->labels->singular_name;
    }

    if ($taxonomy_name === 'storage_locations') {
        $label = 'Storage';
    }

    if ($taxonomy_name === 'item_condition') {
        $label = 'Condition';
    }

    $taxonomy_labels[$taxonomy_name] = $label;
}

// Fetch all terms from the taxonomy
$all_terms = get_terms([
    'taxonomy' => $taxonomies,
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$terms_json = [];
if (!is_wp_error($all_terms)) {
    foreach ($all_terms as $term) {
        $taxonomy_name = (string) $term->taxonomy;
        $taxonomy_label = $taxonomy_labels[$taxonomy_name] ?? $taxonomy_name;
        $value = $taxonomy_name . ':' . (string) $term->term_id;
        $display_name = $term->name;

        if (count($taxonomies) > 1 && $taxonomy_name !== 'item_tag') {
            $display_name = $taxonomy_label . ': ' . $term->name;
        }

        $terms_json[] = [
            'value' => $value,
            'name' => $display_name,
            'taxonomy' => $taxonomy_name,
        ];
    }
}

usort($terms_json, function ($left, $right) {
    $left_value = (string) ($left['value'] ?? '');
    $right_value = (string) ($right['value'] ?? '');

    if ($left_value === 'meta:available' && $right_value !== 'meta:available') {
        return -1;
    }

    if ($right_value === 'meta:available' && $left_value !== 'meta:available') {
        return 1;
    }

    $left_taxonomy = (string) ($left['taxonomy'] ?? '');
    $right_taxonomy = (string) ($right['taxonomy'] ?? '');

    $left_group = ($left_taxonomy === 'storage_locations') ? 1 : 0;
    $right_group = ($right_taxonomy === 'storage_locations') ? 1 : 0;

    if ($left_group !== $right_group) {
        return $left_group <=> $right_group;
    }

    return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});

if (is_array($custom_terms)) {
    foreach ($custom_terms as $custom_term) {
        if (!is_array($custom_term)) {
            continue;
        }

        $value = isset($custom_term['value']) ? trim((string) $custom_term['value']) : '';
        $name = isset($custom_term['name']) ? trim((string) $custom_term['name']) : '';

        if ($value === '' || $name === '') {
            continue;
        }

        $exists = false;
        foreach ($terms_json as $term) {
            if (($term['value'] ?? '') === $value) {
                $exists = true;
                break;
            }
        }

        if ($exists) {
            continue;
        }

        $terms_json[] = [
            'value' => $value,
            'name' => $name,
            'taxonomy' => 'custom',
        ];
    }
}

$default_selected_terms = is_array($default_selected_terms)
    ? array_values(array_filter(array_map(function ($term_value) {
        return trim((string) $term_value);
    }, $default_selected_terms), function ($term_value) {
        return $term_value !== '';
    }))
    : [];

usort($terms_json, function ($left, $right) {
    $left_value = (string) ($left['value'] ?? '');
    $right_value = (string) ($right['value'] ?? '');

    if ($left_value === 'meta:available' && $right_value !== 'meta:available') {
        return -1;
    }

    if ($right_value === 'meta:available' && $left_value !== 'meta:available') {
        return 1;
    }

    $left_taxonomy = (string) ($left['taxonomy'] ?? '');
    $right_taxonomy = (string) ($right['taxonomy'] ?? '');

    $left_group = ($left_taxonomy === 'storage_locations') ? 1 : 0;
    $right_group = ($right_taxonomy === 'storage_locations') ? 1 : 0;

    if ($left_group !== $right_group) {
        return $left_group <=> $right_group;
    }

    return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
});

$terms_data = wp_json_encode($terms_json);
$default_selected_data = wp_json_encode($default_selected_terms);
?>

<div class="filter-tags-control" data-filter-tags-picker-root data-target="<?php echo esc_attr($target); ?>" data-item-selector="<?php echo esc_attr($item_selector); ?>" data-tags-attribute="<?php echo esc_attr($tags_attribute); ?>" data-all-terms="<?php echo esc_attr($terms_data); ?>" data-default-selected-terms="<?php echo esc_attr($default_selected_data); ?>">
    <input
        type="text"
        id="filter-tags-search"
        class="filter-tags-search ui-input"
        placeholder="Add tags..."
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        aria-controls="filter-tags-list"
    >
    <button type="button" class="filter-tags-toggle ui-button" aria-label="Toggle tags list">▾</button>
    
    <div class="filter-tags-list" id="filter-tags-list" hidden>
        <!-- populated by JS -->
    </div>
</div>

