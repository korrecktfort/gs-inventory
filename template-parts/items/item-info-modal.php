<div id="item-modal-trigger" class="item-modal-trigger item-modal-style" hidden>
    <div class="item-modal-backdrop"></div>

    <div class="item-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
        <div class="item-modal-head">
            <h2 id="item-modal-title" class="item-modal-title"></h2>
            <button type="button" class="item-modal-close ui-button" aria-label="Close modal">×</button>
        </div>

        <div class="item-modal-content">
            <?php get_template_part('template-parts/items/item', 'preview', [
                'mode' => 'modal',
                'show_title' => false,
                'root_class' => 'item-preview--modal-card',
            ]); ?>
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
        document.getElementById('item-modal-title').textContent = openButton.dataset.itemName || '';
        document.getElementById('item-modal-stock').textContent = openButton.dataset.itemStock || '';
        document.getElementById('item-modal-notes').textContent = openButton.dataset.itemNotes || '';
        document.getElementById('item-modal-description').textContent = openButton.dataset.itemDescription || '';

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