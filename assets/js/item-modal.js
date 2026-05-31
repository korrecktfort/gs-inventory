const gsItemModalHtmlCache = new Map();

function gsGetModal() {
    return document.getElementById('item-modal-trigger');
}

function gsSetModalOpenState(isOpen) {
    const modal = gsGetModal();
    if (!modal) {
        return;
    }

    modal.hidden = !isOpen;
    document.body.classList.toggle('item-modal-open', isOpen);
}

function gsGetModalHost() {
    const modal = gsGetModal();
    return modal ? modal.querySelector('[data-item-modal-host]') : null;
}

async function gsFetchModalHtml(itemId) {
    if (gsItemModalHtmlCache.has(itemId)) {
        return gsItemModalHtmlCache.get(itemId);
    }

    const body = new URLSearchParams();
    body.set('action', 'gs_get_item_modal_html');
    body.set('item_id', String(itemId));
    body.set('nonce', window.gsItemModalConfig.nonce);

    const response = await fetch(window.gsItemModalConfig.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: body.toString(),
    });

    const payload = await response.json();
    const html = payload.data.html || '';
    gsItemModalHtmlCache.set(itemId, html);
    return html;
}

document.addEventListener('click', async function (event) {
    const openButton = event.target.closest('.open-item-modal');
    const closeButton = event.target.closest('.item-modal-close');
    const backdrop = event.target.closest('.item-modal-backdrop');

    if (openButton) {
        const itemId = Number.parseInt(openButton.dataset.itemId, 10);
        gsSetModalOpenState(true);

        const html = await gsFetchModalHtml(itemId);
        const host = gsGetModalHost();

        if (host) {
            host.innerHTML = html;
        }

        if (typeof window.gsInitItemImageZoom === 'function') {
            window.requestAnimationFrame(function () {
                window.gsInitItemImageZoom();
            });
        }

        return;
    }

    if (closeButton || backdrop) {
        gsSetModalOpenState(false);
    }
});

document.addEventListener('keydown', function (event) {
    const modal = gsGetModal();

    if (!modal || modal.hidden) {
        return;
    }

    if (event.key === 'Escape') {
        modal.hidden = true;
        document.body.classList.remove('item-modal-open');
    }
});
