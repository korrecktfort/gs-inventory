<?php
/**
 * Tag Filter Component (Loaner Picker Pattern)
 * Searchable dropdown of taxonomy tags for filtering items
 * 
 * Args:
 *   - target: CSS selector for container of items to filter
 *   - item_selector: CSS selector for individual item rows
 *   - tags_attribute: data attribute name containing tag IDs (default: data-item-tags)
 *   - taxonomy: taxonomy name (default: item_tag)
 */

$target = $args['target'] ?? '';
$item_selector = $args['item_selector'] ?? '';
$tags_attribute = $args['tags_attribute'] ?? 'data-item-tags';
$taxonomy = $args['taxonomy'] ?? 'item_tag';

// Fetch all terms from the taxonomy
$all_terms = get_terms([
    'taxonomy' => $taxonomy,
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$terms_json = [];
if (!is_wp_error($all_terms)) {
    foreach ($all_terms as $term) {
        $terms_json[] = [
            'id' => $term->term_id,
            'name' => $term->name,
        ];
    }
}
$terms_data = wp_json_encode($terms_json);
?>

<div class="filter-tags-control" data-filter-tags-picker-root data-target="<?php echo esc_attr($target); ?>" data-item-selector="<?php echo esc_attr($item_selector); ?>" data-tags-attribute="<?php echo esc_attr($tags_attribute); ?>" data-all-terms="<?php echo esc_attr($terms_data); ?>">
    <input
        type="text"
        id="filter-tags-search"
        class="filter-tags-search ui-input"
        placeholder="Add tags..."
        autocomplete="off"
        role="combobox"
        aria-autocomplete="list"
        aria-expanded="false"
        aria-controls="filter-tags-list"
    >
    <button type="button" class="filter-tags-toggle ui-button" aria-label="Toggle tags list">▾</button>
    
    <div class="filter-tags-list" id="filter-tags-list" hidden>
        <!-- populated by JS -->
    </div>
</div>

<script>
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
            button.dataset.tagId = term.id;
            button.addEventListener('click', () => selectTag(term.id, term.name));
            tagsList.appendChild(button);
        });
    }
    
    function filterTagOptions() {
        const query = searchInput.value.trim().toLowerCase();
        const options = getTagOptions();
        
        options.forEach((option) => {
            const text = option.textContent.trim().toLowerCase();
            const isMatch = query.length === 0 || text.includes(query);
            const isSelected = selectedTags.some(t => t.id == option.dataset.tagId);
            option.hidden = !isMatch || isSelected;
        });
    }
    
    function selectTag(tagId, tagName) {
        // Prevent duplicates
        if (selectedTags.some(t => t.id == tagId)) return;
        
        selectedTags.push({ id: tagId, name: tagName });
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
        searchInput.value = '';
        searchInput.focus();
    }
    
    function removeTag(tagId) {
        selectedTags = selectedTags.filter(t => t.id != tagId);
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
    }

    function clearAllTags() {
        selectedTags = [];
        renderSelectedTags();
        applyFilter();
        renderTagOptions();
        filterTagOptions();
        searchInput.focus();
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
            removeBtn.addEventListener('click', () => removeTag(tag.id));
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
        const selectedTagIds = selectedTags.map(tag => String(tag.id));
        const items = target.querySelectorAll(itemSelector);
        
        items.forEach((item) => {
            const tagsJson = item.getAttribute(tagsAttribute);
            let itemTags = [];
            
            try {
                if (tagsJson) {
                    const parsed = JSON.parse(tagsJson);
                    itemTags = Array.isArray(parsed) ? parsed.map(String) : [];
                }
            } catch (e) {
                console.warn('Failed to parse tags:', e);
            }
            
            // Show item if no tags selected, or if item has at least one selected tag (OR logic)
            const matches = selectedTagIds.length === 0 || selectedTagIds.some(tagId => itemTags.includes(tagId));
            item.style.display = matches ? '' : 'none';
        });
    }
    
    // Initial render
    renderTagOptions();
    renderSelectedTags();
    closeList();
    
    // Event listeners
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
    
    // Close list when clicking outside
    document.addEventListener('click', (e) => {
        if (!filterRoot.contains(e.target)) {
            closeList();
        }
    });
});
</script>
