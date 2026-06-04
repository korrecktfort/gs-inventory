function parseIntSafe(value) {
    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? 0 : parsed;
}

function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function getRowInput(row) {
    return row ? row.querySelector('.qty-input') : null;
}

function getRowMax(row, input) {
    return parseIntSafe((row && row.dataset && row.dataset.available) || (input && input.max) || 0);
}

function dispatchQuantityEvents(input) {
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function syncRowState(row) {
    const input = getRowInput(row);
    const availability = row ? row.querySelector('.item-availability-value') : null;

    if (!input || !availability) {
        return;
    }

    const selected = parseIntSafe(input.value || 0);
    const availableBase = parseIntSafe((row.dataset && row.dataset.available) || 0);
    const stockTotal = parseIntSafe((row.dataset && row.dataset.stockTotal) || 0);
    const remaining = Math.max(0, availableBase - selected);

    availability.textContent = remaining + '/' + stockTotal;
    row.classList.toggle('is-selected', selected > 0);
}

function collectSelectedItems(rows) {
    const selectedItems = [];

    rows.forEach((row) => {
        const input = getRowInput(row);
        const nameElement = row.querySelector('.item-name');

        if (!input || !nameElement) {
            return;
        }

        const quantity = parseIntSafe(input.value || 0);
        if (quantity <= 0) {
            return;
        }

        selectedItems.push({
            name: nameElement.textContent.trim(),
            quantity,
        });
    });

    return selectedItems;
}

function renderLoanSummary() {
    const summaryList = document.querySelector('.loan-summary-list');

    if (!summaryList) {
        return;
    }

    const rows = document.querySelectorAll('.item-row');
    const selectedItems = collectSelectedItems(rows);

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

function bindQuantityButtonEvents() {
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.qty-btn');

        if (!button) {
            return;
        }

        const row = button.closest('.item-row');
        const input = getRowInput(row);

        if (!row || !input) {
            return;
        }

        const max = getRowMax(row, input);
        const current = parseIntSafe(input.value || 0);
        let next = current;

        if (button.classList.contains('qty-reset')) {
            next = 0;
        } else if (button.classList.contains('qty-minus')) {
            next = Math.max(0, current - 1);
        } else if (button.classList.contains('qty-plus')) {
            next = Math.min(max, current + 1);
        } else if (button.classList.contains('qty-max')) {
            next = max;
        }

        input.value = next;
        dispatchQuantityEvents(input);
    });
}

function bindQuantityInputEvents() {
    document.addEventListener('input', function (event) {
        const input = event.target.closest('.qty-input');

        if (!input) {
            return;
        }

        const row = input.closest('.item-row');
        if (!row) {
            return;
        }

        const max = getRowMax(row, input);
        const value = clamp(parseIntSafe(input.value || 0), 0, max);
        input.value = value;

        syncRowState(row);
        renderLoanSummary();
    });
}

function initLoanItemList() {
    document.querySelectorAll('.item-row').forEach(syncRowState);
    renderLoanSummary();
    bindQuantityButtonEvents();
    bindQuantityInputEvents();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoanItemList);
} else {
    initLoanItemList();
}
