<?php
$item_id = $args['item_id'] ?? 0;

if (!$item_id) {
    return;
}

$item_name = get_the_title($item_id);
$stock_total = (int) get_field('stock_total', $item_id);
$item_notes = get_field('notes', $item_id);
$raw_content = (string) get_post_field('post_content', $item_id);
$item_excerpt = (string) get_the_excerpt($item_id);
$item_description = $item_excerpt !== ''
    ? $item_excerpt
    : wp_trim_words(wp_strip_all_tags($raw_content), 40);
?>

<button
    type="button"
    class="open-item-modal item-info-trigger ui-button"
    data-item-id="<?php echo esc_attr($item_id); ?>"
    data-item-name="<?php echo esc_attr($item_name); ?>"
    data-item-stock="<?php echo esc_attr($stock_total); ?>"
    data-item-notes="<?php echo esc_attr(wp_strip_all_tags((string) $item_notes)); ?>"
    data-item-description="<?php echo esc_attr($item_description); ?>"
>
    <span class="item-info-trigger-label"><?php echo esc_html($item_name); ?></span>
</button>
