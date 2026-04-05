<?php
$item_id = isset($args['item_id']) ? (int) $args['item_id'] : 0;
$mode = $args['mode'] ?? 'default';
$root_class = trim((string) ($args['root_class'] ?? ''));
$title_tag = $args['title_tag'] ?? 'h2';
$title_url = $args['title_url'] ?? '';
$show_title = isset($args['show_title']) ? (bool) $args['show_title'] : true;
$show_taxonomies = isset($args['show_taxonomies']) ? (bool) $args['show_taxonomies'] : false;
$show_data_table = isset($args['show_data_table']) ? (bool) $args['show_data_table'] : false;
$show_image = isset($args['show_image']) ? (bool) $args['show_image'] : true;

$allowed_tags = ['h1', 'h2', 'h3', 'h4', 'p'];
if (!in_array($title_tag, $allowed_tags, true)) {
    $title_tag = 'h2';
}

$can_render_placeholder = ($mode === 'modal');
if (!$item_id && !$can_render_placeholder) {
    return;
}

$item_name = $item_id ? get_the_title($item_id) : '';
$stock_total = $item_id ? (int) get_field('stock_total', $item_id) : 0;

$condition_name = '';
$tag_names = [];
$image_url = '';
$image_alt = '';

if ($item_id && $show_image) {
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
}

if ($image_alt === '') {
    $image_alt = $item_name !== '' ? $item_name : 'Item image';
}

if ($show_taxonomies && $item_id) {
    $condition_value = get_field('condition', $item_id);
    $condition_id = is_numeric($condition_value) ? (int) $condition_value : 0;

    if ($condition_id > 0) {
        $condition_term = get_term($condition_id, 'item_condition');
        if ($condition_term && !is_wp_error($condition_term)) {
            $condition_name = (string) $condition_term->name;
        }
    }

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
}

$article_class = 'item-preview';
if ($mode === 'modal') {
    $article_class .= ' item-preview--modal';
}
if ($root_class !== '') {
    $article_class .= ' ' . $root_class;
}

$stock_label = ($item_id || $mode === 'modal') ? (string) $stock_total : '0';
$is_modal = ($mode === 'modal');
$has_condition = ($condition_name !== '');
$has_tags = !empty($tag_names);
$has_image = ($show_image && $image_url !== '');
$show_stock_in_header = ($show_data_table === false);
$show_data_block = $show_data_table || $has_condition || $has_tags || $has_image;
$render_condition_row = $has_condition || $is_modal;
$render_tags_row = $has_tags || $is_modal;
$render_image_row = $show_image && ($show_data_table || $has_image || $is_modal);
?>

<article class="<?php echo esc_attr($article_class); ?>">
    <header class="item-preview-head">
        <?php if ($show_title) : ?>
            <<?php echo esc_attr($title_tag); ?> class="item-preview-title"<?php echo ($mode === 'modal') ? ' id="item-modal-name"' : ''; ?>>
                <?php if ($title_url !== '') : ?>
                    <a href="<?php echo esc_url($title_url); ?>">
                        <?php echo esc_html($item_name); ?>
                        <span class="item-preview-title-cue" aria-hidden="true">&rarr;</span>
                    </a>
                <?php else : ?>
                    <?php echo esc_html($item_name); ?>
                <?php endif; ?>
            </<?php echo esc_attr($title_tag); ?>>
        <?php endif; ?>

        <?php if ($show_stock_in_header) : ?>
            <p class="item-preview-stock">
                <span class="ui-label">Stock:</span>
                <span class="item-preview-stock-value"<?php echo ($mode === 'modal') ? ' id="item-modal-stock"' : ''; ?>>
                    <?php echo esc_html($stock_label); ?>
                </span>
            </p>
        <?php endif; ?>
    </header>

    <?php if ($show_data_block) : ?>
        <div class="item-preview-data">
            <?php if ($show_data_table) : ?>
                <div class="item-preview-data-row">
                    <span class="item-preview-data-key">Stock:</span>
                    <span class="item-preview-stock-value"<?php echo ($mode === 'modal') ? ' id="item-modal-stock"' : ''; ?>>
                        <?php echo esc_html($stock_label); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($render_condition_row) : ?>
                <div
                    class="item-preview-data-row"
                    <?php echo $is_modal ? 'id="item-modal-condition-row"' : ''; ?>
                    <?php echo ($is_modal && !$has_condition) ? 'hidden' : ''; ?>
                >
                    <span class="item-preview-data-key">Condition:</span>
                    <span
                        class="item-preview-tag item-preview-condition-pill"
                        <?php echo $is_modal ? 'id="item-modal-condition"' : ''; ?>
                    >
                        <?php echo esc_html($condition_name); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($render_tags_row) : ?>
                <div
                    class="item-preview-data-row"
                    <?php echo $is_modal ? 'id="item-modal-tags-row"' : ''; ?>
                    <?php echo ($is_modal && !$has_tags) ? 'hidden' : ''; ?>
                >
                    <span class="item-preview-data-key">Tags:</span>
                    <div
                        class="item-preview-tag-list"
                        aria-label="Item tags"
                        <?php echo $is_modal ? 'id="item-modal-tags"' : ''; ?>
                    >
                        <?php foreach ($tag_names as $tag_name) : ?>
                            <span class="item-preview-tag"><?php echo esc_html($tag_name); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($render_image_row) : ?>
                <div
                    class="item-preview-data-row item-preview-data-row--image"
                    <?php echo $is_modal ? 'id="item-modal-image-row"' : ''; ?>
                >
                    <span class="item-preview-data-key">Image:</span>
                    <div
                        class="item-preview-image-wrap"
                        <?php echo $is_modal ? 'id="item-modal-image-wrap"' : ''; ?>
                        <?php echo (!$has_image) ? 'hidden' : ''; ?>
                    >
                        <img
                            class="item-preview-image"
                            <?php echo $is_modal ? 'id="item-modal-image"' : ''; ?>
                            src="<?php echo esc_url($image_url); ?>"
                            alt="<?php echo esc_attr($image_alt); ?>"
                            loading="lazy"
                            decoding="async"
                            <?php echo (!$has_image) ? 'hidden' : ''; ?>
                        >
                    </div>
                    <span
                        class="item-preview-image-empty"
                        <?php echo $is_modal ? 'id="item-modal-image-empty"' : ''; ?>
                        <?php echo $has_image ? 'hidden' : ''; ?>
                    >
                        No image
                    </span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</article>