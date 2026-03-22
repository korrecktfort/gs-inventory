<?php
$items = get_posts([
    'post_type'      => 'item',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);
?>

<?php 
require_once get_template_directory() . '/inc/loans/loan-queries.php'; 
$loanedQuantities = gs_get_loaned_quantities_map();
?>

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

<section class="item-list">
<div class="item-row <?php echo esc_attr($class_disabled); ?>" data-available="<?php echo esc_attr($available); ?>">
    <div class="item-name column"> 
        <?php echo esc_html($name); ?>
    </div>

    <div class="item-availability column">        
        <?php echo esc_html($available); ?>
        <?php echo "/"; ?>
        <?php echo esc_html($stock_total); ?>     
    </div>

    <div class="item-quantity column">
        <button type="button" class="qty-btn qty-reset">--</button>
        <button type="button" class="qty-btn qty-minus">-</button>
        <input 
        class = "qty-input"
        type="number"
        name="loan_items[<?php echo $id_escaped; ?>][quantity]"
        min="0"
        max="<?php echo esc_attr($available); ?>"
        value="0"        
        >

        <button type="button" class="qty-btn qty-plus">+</button>
        <button type="button" class="qty-btn qty-max">++</button>
    </div>
</div>
<?php endforeach; ?>
</section>

<section class="loan-summary">
    <h2>Selected Items</h2>
    <div class="loan-summary-list">
        <!-- Dynamically populated list of selected items will go here -->
    </div>
</section>
        
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
});

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
        summaryList.innerHTML = '<p>No items selected yet.</p>';
        return;
    }

    summaryList.innerHTML = selectedItems
        .map((item) => {
            return `
                <div class="loan-summary-row" data-item-id="${item.id}">
                    <span class="loan-summary-name">${item.name}</span>
                    <span class="loan-summary-quantity">${item.quantity}</span>
                    <button type="button" class="qty-btn qty-reset-loan" data-item-name="${item.name}">Remove</button>
                </div>
            `;
        })
        .join('');
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('.qty-reset-loan');

    if (!button) {
        return;
    }

    const itemName = button.dataset.itemName;

    if (!itemName) {
        return;
    }

    const rows = document.querySelectorAll('.item-row');

    rows.forEach((row) => {
        const nameElement = row.querySelector('.item-name');

        if (!nameElement) {
            return;
        }

        if (nameElement.textContent.trim() === itemName) {
            const input = row.querySelector('.qty-input');

            if (input) {
                input.value = 0;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    });

    renderLoanSummary();
});

document.addEventListener('input', function (event) {
    if (!event.target.closest('.qty-input')) {
        return;
    }

    renderLoanSummary();
});

document.addEventListener('DOMContentLoaded', function () {
    renderLoanSummary();
});
</script>