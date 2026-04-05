<?php
$item_id = isset($args['item_id']) ? (int) $args['item_id'] : 0;
$mode = $args['mode'] ?? 'default';
$root_class = trim((string) ($args['root_class'] ?? ''));
$title_tag = $args['title_tag'] ?? 'h2';
$title_url = $args['title_url'] ?? '';
$description_source = $args['description_source'] ?? 'excerpt';
$show_title = isset($args['show_title']) ? (bool) $args['show_title'] : true;

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
$item_notes = $item_id ? (string) get_field('notes', $item_id) : '';

$raw_content = $item_id ? (string) get_post_field('post_content', $item_id) : '';
$excerpt_text = $item_id ? (string) get_the_excerpt($item_id) : '';
$description_text = $excerpt_text !== ''
    ? $excerpt_text
    : wp_trim_words(wp_strip_all_tags($raw_content), 40);

$is_content_description = ($description_source === 'content');
$description_html = $is_content_description
    ? apply_filters('the_content', $raw_content)
    : '<p>' . esc_html($description_text) . '</p>';

$notes_text = trim($item_notes);
$notes_display = ($notes_text !== '') ? $notes_text : 'No notes available.';

$article_class = 'item-preview';
if ($mode === 'modal') {
    $article_class .= ' item-preview--modal';
}
if ($root_class !== '') {
    $article_class .= ' ' . $root_class;
}

$stock_label = ($item_id || $mode === 'modal') ? (string) $stock_total : '0';
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

        <p class="item-preview-stock">
            <span class="ui-label">Stock</span>
            <span class="item-preview-stock-value"<?php echo ($mode === 'modal') ? ' id="item-modal-stock"' : ''; ?>>
                <?php echo esc_html($stock_label); ?>
            </span>
        </p>
    </header>

    <div class="item-preview-meta">
        <section class="item-preview-section">
            <h3 class="item-preview-section-title">Notes</h3>
            <p class="item-preview-notes"<?php echo ($mode === 'modal') ? ' id="item-modal-notes"' : ''; ?>>
                <?php echo esc_html($notes_display); ?>
            </p>
        </section>

        <section class="item-preview-section">
            <h3 class="item-preview-section-title">Description</h3>
            <div class="item-preview-description"<?php echo ($mode === 'modal') ? ' id="item-modal-description"' : ''; ?>>
                <?php echo $description_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>
    </div>
</article>