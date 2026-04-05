<?php
$items = get_posts([
    'post_type'      => 'item',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    ]);
    ?>

<section class="item-list" id="loan-item-list">
    <div class="loan-title-field">
        <label for="item-filter" class="ui-label">Select Items</label>
    </div>   
    
<?php 
require_once get_template_directory() . '/inc/loans/loan-queries.php'; 
$loanedQuantities = gs_get_loaned_quantities_map();
?>

<div class="item-list-panel">
    <div class="item-list-toolbar">
        <?php get_template_part('template-parts/ui/filter-input', null, [
        'filter_id' => 'item-filter',
        'placeholder' => 'Filter items...',
        'target' => '#loan-item-list',
        'item_selector' => '.item-row',
        'text_selector' => '.item-info-trigger',
    ]); ?>
    </div>

    <div class="item-list-scroll">
<?php foreach ($items as $item) : ?>
    
    <?php 
        $id = $item->ID;
        $id_escaped = esc_attr($id);
        $name = get_the_title($id);
        $stock_total = (int) get_field('stock_total', $id); 
        $loaned = $loanedQuantities[$id] ?? 0;
        $available = max(0, $stock_total - $loaned);
        $class_disabled = ($available <= 0) ? 'disabled' : '';        
    ?> 


<div class="item-row <?php echo esc_attr($class_disabled); ?>" data-available="<?php echo esc_attr($available); ?>" data-stock-total="<?php echo esc_attr($stock_total); ?>">
    
    <!-- Display Name -->
    <div class="item-name column"> 
        <?php get_template_part( 'template-parts/items/item-info', 'trigger', ['item_id' => $id] ); ?>
    </div>

    <!-- Display Availability -->
    <div class="item-availability column">        
        <p class="item-availability-value"><?php echo esc_html($available) . "/" . esc_html($stock_total); ?></p>
    </div>

    <!-- Assign Quantity To Loan -->
    <div class="item-quantity column">
        <button type="button" class="qty-btn qty-reset ui-button">--</button>
        <button type="button" class="qty-btn qty-minus ui-button">-</button>
        <input 
        class = "qty-input ui-input"
        type="number"
        name="loan_items[<?php echo $id_escaped; ?>][quantity]"
        min="0"
        max="<?php echo esc_attr($available); ?>"
        value="0"        
        >
        <button type="button" class="qty-btn qty-plus ui-button">+</button>
        <button type="button" class="qty-btn qty-max ui-button">++</button>
    </div>

</div>
<?php endforeach; ?>
</div>
</div>
</section>

<section class="loan-summary">    
    <div class="loan-summary-list loan-summary-list--compact">
        <!-- Dynamically populated list of selected items will go here -->
    </div>
</section>

<?php get_template_part('template-parts/items/item-info', 'modal'); ?>
        
<script>    
document.addEventListener('click', function (event) {
    const button = event.target.closest('.qty-btn');

    if (!button) {
        return;
    }

    const row = button.closest('.item-row');

    if (!row) {
        return;
    }

    const input = row.querySelector('.qty-input');

    if (!input) {
        return;
    }

    const max = parseInt(row.dataset.available || input.max || 0, 10);
    const current = parseInt(input.value || 0, 10);

    let next = current;

    if (button.classList.contains('qty-reset')) {
        next = 0;
    }

    if (button.classList.contains('qty-minus')) {
        next = Math.max(0, current - 1);
    }

    if (button.classList.contains('qty-plus')) {
        next = Math.min(max, current + 1);
    }

    if (button.classList.contains('qty-max')) {
        next = max;
    }

    input.value = next;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
});

document.addEventListener('input', function (event) {
    const input = event.target.closest('.qty-input');

    if (!input) {
        return;
    }

    const row = input.closest('.item-row');

    if (!row) {
        return;
    }

    const max = parseInt(row.dataset.available || input.max || 0, 10);
    let value = parseInt(input.value || 0, 10);

    if (isNaN(value)) {
        value = 0;
    }

    value = Math.max(0, Math.min(max, value));
    input.value = value;

    syncRowState(row);
});

function syncRowState(row) {
    const input = row.querySelector('.qty-input');
    const availability = row.querySelector('.item-availability-value');

    if (!input || !availability) {
        return;
    }

    const selected = parseInt(input.value || 0, 10) || 0;
    const availableBase = parseInt(row.dataset.available || 0, 10) || 0;
    const stockTotal = parseInt(row.dataset.stockTotal || 0, 10) || 0;
    const remaining = Math.max(0, availableBase - selected);

    availability.textContent = remaining + '/' + stockTotal;
    row.classList.toggle('is-selected', selected > 0);
}

function renderLoanSummary() {
    const summaryList = document.querySelector('.loan-summary-list');

    if (!summaryList) {
        return;
    }

    const rows = document.querySelectorAll('.item-row');
    const selectedItems = [];

    rows.forEach((row) => {
        const input = row.querySelector('.qty-input');
        const nameElement = row.querySelector('.item-name');

        if (!input || !nameElement) {
            return;
        }

        const quantity = parseInt(input.value || 0, 10);

        if (quantity <= 0) {
            return;
        }

        selectedItems.push({
            name: nameElement.textContent.trim(),
            quantity: quantity,
        });
    });

    if (selectedItems.length === 0) {
        summaryList.innerHTML = '<p class="loan-summary-empty">No items selected yet.</p>';
        return;
    }

    summaryList.innerHTML = `
        <ul class="loan-summary-compact-list">
            ${selectedItems
                .map((item) => `<li><span class="loan-summary-name">${item.name}</span><span class="loan-summary-quantity">${item.quantity}</span></li>`)
                .join('')}
        </ul>
    `;
}

document.addEventListener('input', function (event) {
    if (!event.target.closest('.qty-input')) {
        return;
    }

    renderLoanSummary();
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.item-row').forEach(syncRowState);
    renderLoanSummary();
});
</script>