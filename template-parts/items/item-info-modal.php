<div id="item-modal-trigger" class="item-modal-trigger item-modal-style" hidden>
    <div class="item-modal-backdrop"></div>

    <div class="item-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
        <button type="button" class="item-modal-close ui-button" aria-label="Close modal">×</button>

        <h2 id="item-modal-title">Item Details</h2>

        <div class="item-modal-content">
            <div>
                <strong>Name:</strong>
                <span id="item-modal-name"></span>
            </div>

            <div>
                <strong>Total Stock:</strong>
                <span id="item-modal-stock"></span>
            </div>

            <div>
                <strong>Notes:</strong>
                <div id="item-modal-notes"></div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('click', function (event) {
    const openButton = event.target.closest('.open-item-modal');
    const closeButton = event.target.closest('.item-modal-close');
    const backdrop = event.target.closest('.item-modal-backdrop');
    const modal = document.getElementById('item-modal-trigger');

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
    const modal = document.getElementById('item-modal-trigger');

    if (!modal || modal.hidden) {
        return;
    }

    if (event.key === 'Escape') {
        modal.hidden = true;
    }
});
</script>