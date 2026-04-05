<?php
/**
 * Tag Filter Component (Loaner Picker Pattern)
 * Searchable dropdown of taxonomy tags for filtering items
 * 
 * Args:
 *   - target: CSS selector for container of items to filter
 *   - item_selector: CSS selector for individual item rows
 *   - tags_attribute: data attribute name containing tag IDs (default: data-item-tags)
 *   - taxonomy: single taxonomy name (backward compatible)
 *   - taxonomies: taxonomy names array (preferred)
 */

$target = $args['target'] ?? '';
$item_selector = $args['item_selector'] ?? '';
$tags_attribute = $args['tags_attribute'] ?? 'data-item-filter-terms';

$taxonomies_arg = $args['taxonomies'] ?? ($args['taxonomy'] ?? 'item_tag');
$taxonomies = is_array($taxonomies_arg) ? $taxonomies_arg : [$taxonomies_arg];
$taxonomies = array_values(array_filter(array_unique(array_map('strval', $taxonomies)), function ($taxonomy_name) {
    return taxonomy_exists($taxonomy_name);
}));

if (empty($taxonomies)) {
    $taxonomies = ['item_tag'];
}

$taxonomy_labels = [];
foreach ($taxonomies as $taxonomy_name) {
    $taxonomy_object = get_taxonomy($taxonomy_name);
    $label = $taxonomy_name;

    if ($taxonomy_object && isset($taxonomy_object->labels->singular_name)) {
        $label = (string) $taxonomy_object->labels->singular_name;
    }

    if ($taxonomy_name === 'storage_locations') {
        $label = 'Storage';
    }

    $taxonomy_labels[$taxonomy_name] = $label;
}

// Fetch all terms from the taxonomy
$all_terms = get_terms([
    'taxonomy' => $taxonomies,
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$terms_json = [];
if (!is_wp_error($all_terms)) {
    foreach ($all_terms as $term) {
        $taxonomy_name = (string) $term->taxonomy;
        $taxonomy_label = $taxonomy_labels[$taxonomy_name] ?? $taxonomy_name;
        $value = $taxonomy_name . ':' . (string) $term->term_id;
        $display_name = $term->name;

        if (count($taxonomies) > 1 && $taxonomy_name !== 'item_tag') {
            $display_name = $taxonomy_label . ': ' . $term->name;
        }

        $terms_json[] = [
            'value' => $value,
            'name' => $display_name,
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
        // Prevent duplicates
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
            
            // Show item if no terms selected, or if item has at least one selected term (OR logic)
            const matches = selectedTermValues.length === 0 || selectedTermValues.some(termValue => itemTerms.includes(termValue));
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
