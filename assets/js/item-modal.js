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
        const imageRow = document.getElementById('item-modal-image-row');
        const imageWrap = document.getElementById('item-modal-image-wrap');
        const imageEl = document.getElementById('item-modal-image');
        const imageEmpty = document.getElementById('item-modal-image-empty');

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

        if (imageRow && imageEl) {
            const imageUrl = openButton.dataset.itemImage || '';
            const imageAlt = openButton.dataset.itemImageAlt || openButton.dataset.itemName || 'Item image';
            const imageSrcset = openButton.dataset.itemImageSrcset || '';
            const imageSizes = openButton.dataset.itemImageSizes || '(max-width: 768px) 100vw, 24rem';

            if (imageUrl.length > 0) {
                imageEl.src = imageUrl;
                imageEl.alt = imageAlt;
                if (imageSrcset.length > 0) {
                    imageEl.srcset = imageSrcset;
                } else {
                    imageEl.removeAttribute('srcset');
                }
                imageEl.sizes = imageSizes;
                imageEl.hidden = false;
                if (imageWrap) {
                    imageWrap.hidden = false;
                }
                if (imageEmpty) {
                    imageEmpty.hidden = true;
                }
                imageRow.hidden = false;
            } else {
                imageEl.removeAttribute('src');
                imageEl.alt = imageAlt;
                imageEl.removeAttribute('srcset');
                imageEl.removeAttribute('sizes');
                imageEl.hidden = true;
                if (imageWrap) {
                    imageWrap.hidden = true;
                }
                if (imageEmpty) {
                    imageEmpty.hidden = false;
                }
                imageRow.hidden = false;
            }
        }

        modal.hidden = false;

        if (typeof window.gsInitItemImageZoom === 'function') {
            window.requestAnimationFrame(function () {
                window.gsInitItemImageZoom();
            });
        }

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
