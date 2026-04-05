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
                'show_taxonomies' => true,
                'show_data_table' => true,
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
        const conditionRow = document.getElementById('item-modal-condition-row');
        const conditionValue = document.getElementById('item-modal-condition');
        const tagsRow = document.getElementById('item-modal-tags-row');
        const tagsList = document.getElementById('item-modal-tags');

        document.getElementById('item-modal-title').textContent = openButton.dataset.itemName || '';
        document.getElementById('item-modal-stock').textContent = openButton.dataset.itemStock || '';

        if (conditionRow && conditionValue) {
            const conditionName = openButton.dataset.itemCondition || '';
            conditionValue.textContent = conditionName;
            conditionRow.hidden = conditionName.length === 0;
        }

        if (tagsRow && tagsList) {
            let tagNames = [];
            const rawTags = openButton.dataset.itemTags || '[]';

            try {
                const parsed = JSON.parse(rawTags);
                if (Array.isArray(parsed)) {
                    tagNames = parsed.filter((name) => typeof name === 'string' && name.length > 0);
                }
            } catch (error) {
                tagNames = [];
            }

            tagsList.innerHTML = '';

            tagNames.forEach((tagName) => {
                const pill = document.createElement('span');
                pill.className = 'item-preview-tag';
                pill.textContent = tagName;
                tagsList.appendChild(pill);
            });

            tagsRow.hidden = tagNames.length === 0;
        }

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