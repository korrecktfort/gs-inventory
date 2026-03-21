<?php
// register custom post type for "item" 

function register_item_post_type() {
    $labels = [
        'name' => 'Items',
        'singular_name' => 'Item',
        'menu_name' => 'Items',
        'name_admin_bar' => 'Item',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Item',
        'new_item' => 'New Item',
        'edit_item' => 'Edit Item',
        'view_item' => 'View Item',
        'all_items' => 'All Items',
        'search_items' => 'Search Items',
        'not_found' => 'No items found.',
        'not_found_in_trash' => 'No items found in Trash.',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'items'],
        'menu_icon' => 'dashicons-archive', // You can choose another icon if you prefer
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'show_in_rest' => true,        
        'capability_type' => 'post', // Use 'post' capabilities        
        'taxonomies' => ['post_tag'], // Add default taxonomies if needed
    ];

    register_post_type('item', $args);
}
add_action('init', 'register_item_post_type');

// Register custom post type for "loaner"
function register_loaner_post_type() {
    $labels = [
        'name' => 'Loaners',
        'singular_name' => 'Loaner',
        'menu_name' => 'Loaners',
        'name_admin_bar' => 'Loaner',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Loaner',
        'new_item' => 'New Loaner',
        'edit_item' => 'Edit Loaner',
        'view_item' => 'View Loaner',
        'all_items' => 'All Loaners',
        'search_items' => 'Search Loaners',
        'not_found' => 'No loaners found.',
        'not_found_in_trash' => 'No loaners found in Trash.',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'loaners'],
        'menu_icon' => 'dashicons-groups', // You can choose another icon if you prefer
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
    ];

    register_post_type('loaner', $args);
}
add_action('init', 'register_loaner_post_type');

// register custom post type for "loan"
function register_loan_post_type() {
    $labels = [
        'name' => 'Loans',
        'singular_name' => 'Loan',
        'menu_name' => 'Loans',
        'name_admin_bar' => 'Loan',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Loan',
        'new_item' => 'New Loan',
        'edit_item' => 'Edit Loan',
        'view_item' => 'View Loan',
        'all_items' => 'All Loans',
        'search_items' => 'Search Loans',
        'not_found' => 'No loans found.',
        'not_found_in_trash' => 'No loans found in Trash.',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'loans'],
        'menu_icon' => 'dashicons-clipboard', // You can choose another icon if you prefer
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
    ];

    register_post_type('loan', $args);
}
add_action('init', 'register_loan_post_type');

// Create item columns for the admin list view displaying the acf fields data
// 1. Add custom columns
add_filter('manage_edit-item_columns', 'my_item_columns');
function my_item_columns($columns) {
    // Insert after title or wherever you want  
    $columns['image'] = __('Image');
    $columns['storage_location'] = __('Storage Location');
    $columns['description'] = __('Description');    
    $columns['condition'] = __('Condition');
    $columns['total_quantity'] = __('Total Quantity');    
    $columns['additional_notes'] = __('Additional Notes');
    return $columns;
}

// 2. Fill custom columns with ACF data
add_action('manage_item_posts_custom_column', 'my_item_custom_column', 10, 2);
function my_item_custom_column($column, $post_id) {
    switch ($column) {        
        case 'storage_location':
            $value = get_field('storage_location', $post_id);
            echo esc_html($value ?: '—');
            break;
        case 'description':
            $value = get_field('description', $post_id);
            echo esc_html($value ?: '—');
            break;
        case 'image':
            $value = get_field('image', $post_id);
            if ($value) {
                echo '<img src="' . esc_url($value['url']) . '" alt="' . esc_attr($value['alt']) . '" style="max-width: 100px; height: auto;">';
            } else {
                echo '—';
            }
            break; 
        case 'condition':
            $value = get_field('condition', $post_id);
            echo esc_html($value ?: '—');
            break;
        case 'total_quantity':
            $value = get_field('total_quantity', $post_id);
            echo esc_html($value ?: '—');
            break;
        case 'additional_notes':
            $value = get_field('additional_notes', $post_id);
            echo esc_html($value ?: '—');
            break;
        default:
            echo '—'; // Default case for any other columns
    }
}

function wp_forms_create_post_on_submit_loaner($fields, $entry, $form_data){
    $target_form_id = 38; // loaner form ID
    
    if($form_data['id'] != $target_form_id) {
        return;
    }

    error_log(print_r($fields, true));
    error_log(print_r($entry, true));
    error_log(print_r($form_data, true));

    
    // $name_field = wpforms_get_fields_value($fields, '6');
    $loaner_full_name = $fields[6]['value'] ?? '';
    $loaner_company = $fields[10]['value'] ?? '';
    $loaner_address = $fields[3]['value'] ?? '';
    $loaner_mail = $fields[5]['value'] ?? '';
    $loaner_phone = $fields[4]['value'] ?? '';
    $loaner_accepted_terms = !empty($fields[8]['value']);
    $loaner_notes = $fields[7]['value'] ?? '';

    // Create a new post of type 'loaner'
    $post_id = wp_insert_post([
        'post_title' => $loaner_full_name,        
        'post_content' => $loaner_company . '; ' . $loaner_address . '; ' . $loaner_mail . '; ' . $loaner_phone . '; ' . $loaner_accepted_terms . '; ' . $loaner_notes, // You can add content if needed
        'post_type' => 'loaner',
        'post_status' => 'publish',
    ]);

    if($post_id) {
        update_field('loaner-full-name', $loaner_full_name, $post_id);
        update_field('loaner-address', $loaner_address, $post_id);
        update_field('loaner-company', $loaner_company, $post_id);
        update_field('loaner-mail', $loaner_mail, $post_id);
        update_field('loaner-phone', $loaner_phone, $post_id);
        update_field('loaner-accepted-terms', $loaner_accepted_terms, $post_id);
        update_field('loaner-notes', $loaner_notes, $post_id);
    }   
}
add_action('wpforms_process_complete', 'wp_forms_create_post_on_submit_loaner', 10, 3);

function wpforms_get_fields_value($fields, $id) {
    foreach ($fields as $field) {
        if ($field['id'] == $id) {
            return $field['value'];
        }
    }
    return '';
}
?>