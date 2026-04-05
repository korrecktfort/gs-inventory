document.addEventListener('DOMContentLoaded', function () {
    const filterRoot = document.querySelector('[data-filter-tags-picker-root]');

    if (!filterRoot) return;

    const target = document.querySelector(filterRoot.dataset.target);
    const itemSelector = filterRoot.dataset.itemSelector;
    const tagsAttribute = filterRoot.dataset.tagsAttribute;
    const searchInput = filterRoot.querySelector('#filter-tags-search');
    const toggleButton = filterRoot.querySelector('.filter-tags-toggle');
    const tagsList = filterRoot.querySelector('#filter-tags-list');
    const selectedContainer = document.querySelector('#filter-tags-selected');
    const filterPanel = filterRoot.closest('.item-list-panel') || document;

    if (!target || !itemSelector || !searchInput || !toggleButton || !tagsList || !selectedContainer) return;

    let allTerms = [];
    try {
        allTerms = JSON.parse(filterRoot.dataset.allTerms || '[]');
    } catch (e) {
        console.warn('Failed to parse terms:', e);
    }

    let selectedTags = [];

    function getTagOptions() {
        return tagsList.querySelectorAll('.filter-tags-option');
    }

    function closeList() {
        tagsList.hidden = true;
        searchInput.setAttribute('aria-expanded', 'false');
    }

    function openList() {
        tagsList.hidden = false;
        searchInput.setAttribute('aria-expanded', 'true');
    }

    function renderTagOptions() {
        tagsList.innerHTML = '';

        allTerms.forEach((term) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'filter-tags-option';
            button.textContent = term.name;
            button.dataset.termValue = term.value;
            button.addEventListener('click', () => selectTag(term.value, term.name));
            tagsList.appendChild(button);
        });
    }

    function filterTagOptions() {
        const query = searchInput.value.trim().toLowerCase();
        const options = getTagOptions();

        options.forEach((option) => {
            const text = option.textContent.trim().toLowerCase();
            const isMatch = query.length === 0 || text.includes(query);
            const isSelected = selectedTags.some(t => t.value === option.dataset.termValue);
            option.hidden = !isMatch || isSelected;
        });
    }

    function selectTag(termValue, termName) {
        if (selectedTags.some(t => t.value === termValue)) return;

        selectedTags.push({ value: termValue, name: termName });
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
        searchInput.value = '';
        searchInput.focus();
    }

    function removeTag(termValue) {
        selectedTags = selectedTags.filter(t => t.value !== termValue);
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
        syncQuickFilterChips();
    }

    function clearAllTags() {
        selectedTags = [];
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
        syncQuickFilterChips();
        searchInput.focus();
    }

    function syncQuickFilterChips() {
        const chips = filterPanel.querySelectorAll('.item-quick-filter-chip[data-term-value]');

        chips.forEach((chip) => {
            const termValue = chip.getAttribute('data-term-value') || '';
            const active = selectedTags.some((tag) => tag.value === termValue);
            chip.classList.toggle('is-active', active);
            chip.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    function renderSelectedTags() {
        selectedContainer.innerHTML = '';

        if (selectedTags.length === 0) {
            return;
        }

        selectedTags.forEach((tag) => {
            const pill = document.createElement('div');
            pill.className = 'filter-tags-pill';

            const name = document.createElement('span');
            name.className = 'filter-tags-pill-name';
            name.textContent = tag.name;
            pill.appendChild(name);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'filter-tags-pill-remove ui-button';
            removeBtn.innerHTML = '×';
            removeBtn.setAttribute('aria-label', 'Remove ' + tag.name);
            removeBtn.addEventListener('click', () => removeTag(tag.value));
            pill.appendChild(removeBtn);

            selectedContainer.appendChild(pill);
        });

        const clearAllBtn = document.createElement('button');
        clearAllBtn.type = 'button';
        clearAllBtn.className = 'filter-tags-clear-all ui-button';
        clearAllBtn.textContent = 'Clear tags';
        clearAllBtn.setAttribute('aria-label', 'Clear all selected tags');
        clearAllBtn.addEventListener('click', clearAllTags);
        selectedContainer.appendChild(clearAllBtn);
    }

    function applyFilter() {
        const selectedTermValues = selectedTags.map(tag => String(tag.value));
        const items = target.querySelectorAll(itemSelector);

        items.forEach((item) => {
            const tagsJson = item.getAttribute(tagsAttribute);
            let itemTerms = [];

            try {
                if (tagsJson) {
                    const parsed = JSON.parse(tagsJson);
                    itemTerms = Array.isArray(parsed) ? parsed.map(String) : [];
                }
            } catch (e) {
                console.warn('Failed to parse tags:', e);
            }

            const matches = selectedTermValues.length === 0 || selectedTermValues.some(termValue => itemTerms.includes(termValue));
            item.style.display = matches ? '' : 'none';
        });

        target.dispatchEvent(new CustomEvent('gs:filter-updated', { bubbles: true }));
    }

    renderTagOptions();
    renderSelectedTags();
    closeList();

    toggleButton.addEventListener('click', () => {
        if (tagsList.hidden) {
            openList();
            searchInput.focus();
        } else {
            closeList();
        }
    });

    searchInput.addEventListener('focus', () => {
        if (selectedTags.length < allTerms.length) {
            openList();
        }
    });

    searchInput.addEventListener('input', filterTagOptions);

    filterPanel.addEventListener('click', (event) => {
        const chip = event.target.closest('.item-quick-filter-chip[data-term-value]');

        if (!chip) {
            return;
        }

        const termValue = chip.getAttribute('data-term-value') || '';
        const termName = chip.getAttribute('data-term-name') || chip.textContent.trim();

        if (termValue === '') {
            return;
        }

        if (selectedTags.some((tag) => tag.value === termValue)) {
            removeTag(termValue);
            return;
        }

        selectTag(termValue, termName);
    });

    document.addEventListener('click', (e) => {
        if (!filterRoot.contains(e.target)) {
            closeList();
        }
    });

    syncQuickFilterChips();
});
