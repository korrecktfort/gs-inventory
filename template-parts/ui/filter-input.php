<?php
$filter_id = $args['filter_id'] ?? 'list-filter';
$placeholder = $args['placeholder'] ?? 'Search...';
$target = $args['target'] ?? '';
$item_selector = $args['item_selector'] ?? '';
$text_selector = $args['text_selector'] ?? '';
?>

<div
    class="filter-input"
    data-filter-root
    data-filter-target="<?php echo esc_attr($target); ?>"
    data-filter-selector="<?php echo esc_attr($item_selector); ?>"
    data-filter-text-selector="<?php echo esc_attr($text_selector); ?>"
>
    <input
        type="text"
        id="<?php echo esc_attr($filter_id); ?>"
        class="filter-input-field ui-input"
        placeholder="<?php echo esc_attr($placeholder); ?>"
        autocomplete="off"
    >

    <button
        type="button"
        class="filter-input-clear ui-button"
        aria-label="Clear search"
        hidden
    >
        ×
    </button>
</div>
