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
