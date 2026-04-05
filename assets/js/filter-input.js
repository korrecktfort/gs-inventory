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
