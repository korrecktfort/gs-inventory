<?php
$index = $args['index'] ?? 0;
$items = $args['items'] ?? [];
?>

<div style="margin-bottom: 1rem;">
    <label for="loan_items_<?php echo $index; ?>_item">Item</label>
    <select name="loan_items[<?php echo $index; ?>][item]" id="loan_items_<?php echo $index; ?>_item">
        <option value="">-- auswählen --</option>
        <?php foreach ($items as $item) : ?>
            <option value="<?php echo esc_attr($item->ID); ?>">
                <?php echo esc_html($item->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="loan_items_<?php echo $index; ?>_quantity">Quantity</label>
    <input
        type="number"
        name="loan_items[<?php echo $index; ?>][quantity]"
        id="loan_items_<?php echo $index; ?>_quantity"
        min="1"
        step="1"
    >
</div>