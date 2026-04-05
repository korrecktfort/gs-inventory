document.addEventListener('DOMContentLoaded', function () {
    // Return guard: each loan card carries the IDs in data attributes on its article.
    document.querySelectorAll('.loan-single-card[data-guard-input-id][data-submit-id]').forEach(function (card) {
        const guardInput = document.getElementById(card.dataset.guardInputId);
        const submitButton = document.getElementById(card.dataset.submitId);

        if (!guardInput || !submitButton) {
            return;
        }

        const expected = guardInput.dataset.expected || '';

        function updateReturnButtonState() {
            const entered = (guardInput.value || '').trim();
            submitButton.disabled = entered !== expected;
        }

        guardInput.addEventListener('input', updateReturnButtonState);
        updateReturnButtonState();
    });

    // Items toggle: each loan card carries the IDs in data attributes on its article.
    document.querySelectorAll('.loan-single-card[data-toggle-id][data-content-id]').forEach(function (card) {
        const itemsToggle = document.getElementById(card.dataset.toggleId);
        const itemsContent = document.getElementById(card.dataset.contentId);

        if (!itemsToggle || !itemsContent) {
            return;
        }

        itemsToggle.addEventListener('click', function () {
            const isExpanded = itemsToggle.getAttribute('aria-expanded') === 'true';
            const nextExpanded = !isExpanded;

            itemsToggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            itemsContent.hidden = !nextExpanded;
        });
    });
});
