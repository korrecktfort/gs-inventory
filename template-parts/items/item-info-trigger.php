<?php
$item_id = $args['item_id'] ?? 0;

if (!$item_id) {
    return;
}

$item_name = get_the_title($item_id);
$stock_total = (int) get_field('stock_total', $item_id);

$image_url = '';
$image_alt = '';
$image_field = get_field('image', $item_id);
$image_id = 0;

if (is_array($image_field)) {
    $image_id = (int) ($image_field['ID'] ?? $image_field['id'] ?? 0);

    if ($image_id <= 0 && !empty($image_field['url'])) {
        $image_url = (string) $image_field['url'];
    }

    if (!empty($image_field['alt']) && is_string($image_field['alt'])) {
        $image_alt = trim($image_field['alt']);
    }
} elseif (is_numeric($image_field)) {
    $image_id = (int) $image_field;
}

if ($image_id <= 0 && has_post_thumbnail($item_id)) {
    $image_id = (int) get_post_thumbnail_id($item_id);
}

if ($image_id > 0) {
    $image_src = wp_get_attachment_image_src($image_id, 'large');
    if (is_array($image_src) && !empty($image_src[0])) {
        $image_url = (string) $image_src[0];
    }

    if ($image_alt === '') {
        $image_alt_meta = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        if (is_string($image_alt_meta)) {
            $image_alt = trim($image_alt_meta);
        }
    }
}

if ($image_alt === '') {
    $image_alt = $item_name;
}

$condition_name = '';
$condition_value = get_field('condition', $item_id);
$condition_id = is_numeric($condition_value) ? (int) $condition_value : 0;

if ($condition_id > 0) {
    $condition_term = get_term($condition_id, 'item_condition');
    if ($condition_term && !is_wp_error($condition_term)) {
        $condition_name = (string) $condition_term->name;
    }
}

$tag_names = [];
$tags_value = get_field('tags', $item_id);
$tag_ids = [];

if (is_array($tags_value)) {
    $tag_ids = $tags_value;
} elseif (is_numeric($tags_value)) {
    $tag_ids = [(int) $tags_value];
}

foreach ($tag_ids as $tag_id_raw) {
    $tag_id = (int) $tag_id_raw;
    if ($tag_id <= 0) {
        continue;
    }

    $tag_term = get_term($tag_id, 'item_tag');
    if ($tag_term && !is_wp_error($tag_term)) {
        $tag_names[] = (string) $tag_term->name;
    }
}

$tag_names = array_values(array_unique($tag_names));
?>

<button
    type="button"
    class="open-item-modal item-info-trigger ui-button"
    data-item-id="<?php echo esc_attr($item_id); ?>"
    data-item-name="<?php echo esc_attr($item_name); ?>"
    data-item-stock="<?php echo esc_attr($stock_total); ?>"
    data-item-condition="<?php echo esc_attr($condition_name); ?>"
    data-item-tags="<?php echo esc_attr(wp_json_encode($tag_names)); ?>"
    data-item-image="<?php echo esc_url($image_url); ?>"
    data-item-image-alt="<?php echo esc_attr($image_alt); ?>"
>
    <span class="item-info-trigger-label"><?php echo esc_html($item_name); ?></span>
</button>
