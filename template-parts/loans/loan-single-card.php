<?php
$loan_id = isset($args['loan_id']) ? (int) $args['loan_id'] : get_the_ID();

if (!$loan_id) {
    return;
}

$show_return_form = array_key_exists('show_return_form', $args ?? []) ? (bool) $args['show_return_form'] : true;
$items_expanded = array_key_exists('items_expanded', $args ?? []) ? (bool) $args['items_expanded'] : $show_return_form;
$title_tag = (string) ($args['title_tag'] ?? 'h1');
$title_tag = in_array($title_tag, ['h1', 'h2', 'h3'], true) ? $title_tag : 'h1';
$title_url = isset($args['title_url']) ? (string) $args['title_url'] : '';
$root_class = isset($args['root_class']) ? trim((string) $args['root_class']) : '';

$loan_items = get_posts([
    'post_type'      => 'loan_item',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => 'related_loan',
            'value'   => $loan_id,
            'compare' => '=',
        ],
    ],
]);

$loaner = get_field('related_loaner', $loan_id);
$loaner_id = is_object($loaner) ? (int) ($loaner->ID ?? 0) : (int) $loaner;
$loaner_url = $loaner_id > 0 ? get_permalink($loaner_id) : '';
$start_date = get_field('start_date', $loan_id);
$due_date = get_field('due_date', $loan_id);
$status = get_field('status', $loan_id);
$loan_user_id = (int) get_field('user', $loan_id);

if ($loan_user_id <= 0) {
    $loan_user_id = (int) get_post_field('post_author', $loan_id);
}

$loan_user = $loan_user_id > 0 ? get_userdata($loan_user_id) : false;
$loan_user_name = $loan_user ? (string) $loan_user->display_name : '';
$loan_user_email = ($loan_user && !empty($loan_user->user_email)) ? (string) $loan_user->user_email : '';
$status_label = (string) ($status ?: 'unknown');
$status_key = sanitize_html_class(strtolower($status_label));

$format_date_for_display = static function ($date_value) {
    if (empty($date_value)) {
        return '—';
    }

    $raw = trim((string) $date_value);
    $timestamp = false;

    foreach (['Y-m-d', 'Ymd', 'd/m/Y', 'm/d/Y', 'd.m.Y'] as $format) {
        $dt = DateTime::createFromFormat($format, $raw);

        if ($dt instanceof DateTime) {
            $timestamp = $dt->getTimestamp();
            break;
        }
    }

    if ($timestamp === false) {
        $parsed = strtotime($raw);

        if ($parsed !== false) {
            $timestamp = $parsed;
        }
    }

    if ($timestamp === false) {
        return $raw;
    }

    return sprintf('%s (%s)', wp_date('d.m.Y', $timestamp), wp_date('l', $timestamp));
};

$start_date_display = $format_date_for_display($start_date);
$due_date_display = $format_date_for_display($due_date);
$return_guard_code = (string) wp_rand(100, 999);
$guard_input_id = 'return-confirm-code-' . $loan_id;
$submit_button_id = 'return-loan-submit-' . $loan_id;
$items_toggle_id = 'loan-items-toggle-' . $loan_id;
$items_content_id = 'loan-items-content-' . $loan_id;
$article_class = trim('loan-single-card ' . $root_class);
?>

<article class="<?php echo esc_attr($article_class); ?>">
    <div class="loan-single-head">
        <<?php echo esc_attr($title_tag); ?> class="loan-single-title">
            <?php if ($title_url !== '') : ?>
            <a class="gs-arrow-link" href="<?php echo esc_url($title_url); ?>">
                <?php echo esc_html(get_the_title($loan_id)); ?>
            </a>
            <?php else : ?>
            <?php echo esc_html(get_the_title($loan_id)); ?>
            <?php endif; ?>
        </<?php echo esc_attr($title_tag); ?>>
        <span class="loan-status-pill loan-status-pill--<?php echo esc_attr($status_key); ?>">
            <?php echo esc_html($status_label); ?>
        </span>
    </div>

    <div class="loan-single-meta">
    <?php if ($loaner) : ?>
    <div class="loan-single-meta-item">
        <span class="ui-label">Loaner</span>
        <?php if (!empty($loaner_url)) : ?>
            <a href="<?php echo esc_url($loaner_url); ?>"><?php echo esc_html(get_the_title($loaner_id)); ?></a>
        <?php else : ?>
            <span><?php echo esc_html(get_the_title($loaner)); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($loan_user_name !== '') : ?>
    <div class="loan-single-meta-item">
        <span class="ui-label">Created By</span>
        <span>
            <?php echo esc_html($loan_user_name); ?>
            <?php if ($loan_user_email !== '') : ?>
                <?php echo esc_html(' (' . $loan_user_email . ')'); ?>
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="loan-single-meta-item">
        <span class="ui-label">Start Date</span>
        <span><?php echo esc_html($start_date_display); ?></span>
    </div>

    <div class="loan-single-meta-item">
        <span class="ui-label">Return Date</span>
        <span><?php echo esc_html($due_date_display); ?></span>
    </div>

    </div>

    <section class="loan-single-items">
    <button
        type="button"
        id="<?php echo esc_attr($items_toggle_id); ?>"
        class="loan-single-items-toggle"
        aria-expanded="<?php echo $items_expanded ? 'true' : 'false'; ?>"
        aria-controls="<?php echo esc_attr($items_content_id); ?>"
    >
        <span class="loan-single-items-caret" aria-hidden="true">&gt;</span>
        <span class="loan-single-items-heading">Loaned Items</span>
    </button>

    <div
        id="<?php echo esc_attr($items_content_id); ?>"
        class="loan-single-items-content"
        <?php if (!$items_expanded) : ?>hidden<?php endif; ?>
    >
        <?php if ($loan_items) : ?>
            <ul class="loan-single-item-list">
            <?php foreach ($loan_items as $loan_item) : ?>
                <?php
                $item_id = (int) get_field('related_item', $loan_item->ID);
                $quantity = (int) get_field('quantity', $loan_item->ID);
                $stock_total = (int) get_field('stock_total', $item_id);
                ?>
                <?php if (!$item_id) { continue; } ?>
                <li class="loan-single-item-row">
                    <div class="loan-single-item-name">
                        <?php get_template_part('template-parts/items/item-info', 'trigger', ['item_id' => $item_id]); ?>
                    </div>
                    <div class="loan-single-item-quantity">
                        <span class="loan-summary-quantity"><?php echo esc_html($quantity . '/' . $stock_total); ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="loan-single-items-empty">No items found for this loan.</p>
        <?php endif; ?>
    </div>
    </section>

    <?php if ($show_return_form && $status === 'active') : ?>
    <form method="post" class="loan-single-return-form">
        <?php wp_nonce_field('return_loan_action', 'return_loan_nonce'); ?>
        <input type="hidden" name="loan_id" value="<?php echo esc_attr($loan_id); ?>">
        <div class="loan-single-return-inline">
            <span class="loan-single-return-guard-code"><?php echo esc_html($return_guard_code); ?></span>
            <input
                type="number"
                id="<?php echo esc_attr($guard_input_id); ?>"
                class="ui-input loan-single-return-guard-input"
                inputmode="numeric"
                min="100"
                max="999"
                step="1"
                autocomplete="off"
                aria-label="Enter the 3-digit confirmation code"
                data-expected="<?php echo esc_attr($return_guard_code); ?>"
            >
            <button
                type="submit"
                name="return_loan_submit"
                value="1"
                class="ui-button"
                id="<?php echo esc_attr($submit_button_id); ?>"
                disabled
            >
                Mark as returned
            </button>
        </div>
    </form>

    <script>
    (function () {
        const guardInput = document.getElementById('<?php echo esc_js($guard_input_id); ?>');
        const submitButton = document.getElementById('<?php echo esc_js($submit_button_id); ?>');

        if (!guardInput || !submitButton) {
            return;
        }

        const expected = guardInput.dataset.expected || '';

        function updateReturnButtonState() {
            const entered = (guardInput.value || '').trim();
            submitButton.disabled = entered !== expected;
        }

        guardInput.addEventListener('input', updateReturnButtonState);
        updateReturnButtonState();
    })();
    </script>
    <?php endif; ?>

    <script>
    (function () {
        const itemsToggle = document.getElementById('<?php echo esc_js($items_toggle_id); ?>');
        const itemsContent = document.getElementById('<?php echo esc_js($items_content_id); ?>');

        if (!itemsToggle || !itemsContent) {
            return;
        }

        itemsToggle.addEventListener('click', function () {
            const isExpanded = itemsToggle.getAttribute('aria-expanded') === 'true';
            const nextExpanded = !isExpanded;

            itemsToggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            itemsContent.hidden = !nextExpanded;
        });
    })();
    </script>
</article>
