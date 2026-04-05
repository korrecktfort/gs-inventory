<?php
$filter_id = $args['filter_id'] ?? 'list-filter';
$placeholder = $args['placeholder'] ?? 'Search...';
$target = $args['target'] ?? '';
$item_selector = $args['item_selector'] ?? '';
$text_selector = $args['text_selector'] ?? '';
?>

<div
    class="filter-input"
    data-filter-root
    data-filter-target="<?php echo esc_attr($target); ?>"
    data-filter-selector="<?php echo esc_attr($item_selector); ?>"
    data-filter-text-selector="<?php echo esc_attr($text_selector); ?>"
>
    <input
        type="text"
        id="<?php echo esc_attr($filter_id); ?>"
        class="filter-input-field ui-input"
        placeholder="<?php echo esc_attr($placeholder); ?>"
        autocomplete="off"
    >

    <button
        type="button"
        class="filter-input-clear ui-button"
        aria-label="Clear search"
        hidden
    >
        ×
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterRoots = document.querySelectorAll('[data-filter-root]');

    filterRoots.forEach((root) => {
        const input = root.querySelector('.filter-input-field');
        const clearButton = root.querySelector('.filter-input-clear');
        const targetSelector = root.dataset.filterTarget;
        const itemSelector = root.dataset.filterSelector;
        const textSelector = root.dataset.filterTextSelector;

        if (!input || !clearButton || !targetSelector || !itemSelector || !textSelector) {
            return;
        }

        const target = document.querySelector(targetSelector);

        if (!target) {
            return;
        }

        function applyFilter() {
            const query = input.value.trim().toLowerCase();
            const items = target.querySelectorAll(itemSelector);

            clearButton.hidden = query.length === 0;

            items.forEach((item) => {
                const textNode = item.querySelector(textSelector);
                const haystack = textNode ? textNode.textContent.trim().toLowerCase() : '';

                const matches = query.length === 0 || haystack.includes(query);

                item.style.display = matches ? '' : 'none';
            });

            target.dispatchEvent(new CustomEvent('gs:filter-updated', { bubbles: true }));
        }

        input.addEventListener('input', applyFilter);

        clearButton.addEventListener('click', function () {
            input.value = '';
            input.focus();
            applyFilter();
        });

        applyFilter();
    });
});
</script>