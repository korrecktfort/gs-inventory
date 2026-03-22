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
    class="open-item-modal item-info-trigger"
    data-item-id="<?php echo esc_attr($item_id); ?>"
    data-item-name="<?php echo esc_attr($item_name); ?>"
    data-item-stock="<?php echo esc_attr($stock_total); ?>"
    data-item-notes="<?php echo esc_attr(wp_strip_all_tags((string) $item_notes)); ?>"
>
    <?php echo esc_html($item_name); ?>
</button>

<script>
    document.addEventListener('click', function (event) {
    const openButton = event.target.closest('.open-item-modal');
    const closeButton = event.target.closest('.item-modal-close');
    const backdrop = event.target.closest('.item-modal-backdrop');
    const modal = document.getElementById('item-modal');

    if (!modal) {
        return;
    }

    if (openButton) {
        document.getElementById('item-modal-name').textContent = openButton.dataset.itemName || '';
        document.getElementById('item-modal-stock').textContent = openButton.dataset.itemStock || '';
        document.getElementById('item-modal-notes').textContent = openButton.dataset.itemNotes || '';

        modal.hidden = false;
        return;
    }

    if (closeButton || backdrop) {
        modal.hidden = true;
    }
});

document.addEventListener('keydown', function (event) {
    const modal = document.getElementById('item-modal');

    if (!modal || modal.hidden) {
        return;
    }

    if (event.key === 'Escape') {
        modal.hidden = true;
    }
});
</script>