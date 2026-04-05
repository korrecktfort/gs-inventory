<?php
$index = $args['index'] ?? 0;
$items = $args['items'] ?? [];
?>

<div class="loan-item-row-field">
    <label for="loan_items_<?php echo $index; ?>_item" class="ui-label">Item</label>
    <select name="loan_items[<?php echo $index; ?>][item]" id="loan_items_<?php echo $index; ?>_item" class="ui-input">
        <option value="">-- auswählen --</option>
        <?php foreach ($items as $item) : ?>
            <option value="<?php echo esc_attr($item->ID); ?>">
                <?php echo esc_html($item->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="loan_items_<?php echo $index; ?>_quantity" class="ui-label">Quantity</label>
    <input
        class="ui-input"
        type="number"
        name="loan_items[<?php echo $index; ?>][quantity]"
        id="loan_items_<?php echo $index; ?>_quantity"
        min="1"
        step="1"
    >
</div>