document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('item-list-overview');
    const rowsWrap = root ? root.querySelector('.item-overview-rows') : null;
    const emptyFiltered = document.getElementById('item-overview-filter-empty');

    if (!root || !rowsWrap || !emptyFiltered) {
        return;
    }

    function updateFilteredEmptyState() {
        const rows = rowsWrap.querySelectorAll('.item-overview-row');
        const visibleCount = Array.from(rows).filter((row) => row.style.display !== 'none' && !row.hidden).length;

        emptyFiltered.hidden = visibleCount !== 0;
    }

    root.addEventListener('gs:filter-updated', updateFilteredEmptyState);
    updateFilteredEmptyState();
});
