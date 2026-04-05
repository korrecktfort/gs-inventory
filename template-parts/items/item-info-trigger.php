<?php
$item_id = $args['item_id'] ?? 0;

if (!$item_id) {
    return;
}

$item_name = get_the_title($item_id);
$stock_total = (int) get_field('stock_total', $item_id);
$item_notes = get_field('notes', $item_id);
?>

<button
    type="button"
    class="open-item-modal item-info-trigger ui-button"
    data-item-id="<?php echo esc_attr($item_id); ?>"
    data-item-name="<?php echo esc_attr($item_name); ?>"
    data-item-stock="<?php echo esc_attr($stock_total); ?>"
    data-item-notes="<?php echo esc_attr(wp_strip_all_tags((string) $item_notes)); ?>"
>
    <span class="item-info-trigger-label"><?php echo esc_html($item_name); ?></span>
</button>
