<?php
// register custom post type for "item" 

function enqueue_style(){
    wp_enqueue_style('gs-inventory-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_script(
        'gs-item-image-zoom',
        get_template_directory_uri() . '/assets/js/item-image-zoom.js',
        [],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_style');

function gs_register_theme_menus() {
    register_nav_menus([
        'main-menu' => 'Main Menu',
    ]);
}
add_action('after_setup_theme', 'gs_register_theme_menus');

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
        'taxonomies' => ['item_tag', 'item_condition', 'storage_locations'],
    ];

    register_post_type('item', $args);
}
add_action('init', 'register_item_post_type');

function register_item_taxonomies() {
    $labels = [
        'name' => 'Item Tags',
        'singular_name' => 'Item Tag',
        'search_items' => 'Search Item Tags',
        'popular_items' => 'Popular Item Tags',
        'all_items' => 'All Item Tags',
        'edit_item' => 'Edit Item Tag',
        'update_item' => 'Update Item Tag',
        'add_new_item' => 'Add New Item Tag',
        'new_item_name' => 'New Item Tag Name',
        'separate_items_with_commas' => 'Separate item tags with commas',
        'add_or_remove_items' => 'Add or remove item tags',
        'choose_from_most_used' => 'Choose from the most used item tags',
        'menu_name' => 'Item Tags',
    ];

    $args = [
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => ['slug' => 'item-tag'],
        'show_in_rest' => true,
    ];

    register_taxonomy('item_tag', ['item'], $args);
}
add_action('init', 'register_item_taxonomies');

function register_item_condition_taxonomy() {
    $labels = [
        'name' => 'Item Conditions',
        'singular_name' => 'Item Condition',
        'search_items' => 'Search Item Conditions',
        'all_items' => 'All Item Conditions',
        'parent_item' => 'Parent Item Condition',
        'parent_item_colon' => 'Parent Item Condition:',
        'edit_item' => 'Edit Item Condition',
        'update_item' => 'Update Item Condition',
        'add_new_item' => 'Add New Item Condition',
        'new_item_name' => 'New Item Condition Name',
        'menu_name' => 'Item Conditions',
    ];

    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'item-condition'],
        'show_in_rest' => true,
    ];

    register_taxonomy('item_condition', ['item'], $args);
}
add_action('init', 'register_item_condition_taxonomy');

function register_storage_locations_taxonomy() {
    $labels = [
        'name' => 'Storage Locations',
        'singular_name' => 'Storage Location',
        'search_items' => 'Search Storage Locations',
        'all_items' => 'All Storage Locations',
        'parent_item' => 'Parent Storage Location',
        'parent_item_colon' => 'Parent Storage Location:',
        'edit_item' => 'Edit Storage Location',
        'update_item' => 'Update Storage Location',
        'add_new_item' => 'Add New Storage Location',
        'new_item_name' => 'New Storage Location Name',
        'menu_name' => 'Storage Locations',
    ];

    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'storage-location'],
        'show_in_rest' => true,
    ];

    register_taxonomy('storage_locations', ['item'], $args);
}
add_action('init', 'register_storage_locations_taxonomy');

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

// // Register custom post type for "location"
// function register_location_post_type() {
//     $labels = [
//         'name' => 'Locations',
//         'singular_name' => 'Location',
//         'menu_name' => 'Locations',
//         'name_admin_bar' => 'Location',
//         'add_new' => 'Add New',
//         'add_new_item' => 'Add New Location',
//         'new_item' => 'New Location',
//         'edit_item' => 'Edit Location',
//         'view_item' => 'View Location',
//         'all_items' => 'All Locations',
//         'search_items' => 'Search Locations',
//         'not_found' => 'No locations found.',
//         'not_found_in_trash' => 'No locations found in Trash.',
//     ];

//     $args = [
//         'labels' => $labels,
//         'public' => true,
//         'has_archive' => true,
//         'rewrite' => ['slug' => 'locations'],
//         'menu_icon' => 'dashicons-location',
//         'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
//         'show_in_rest' => true,
//         'capability_type' => 'post',
//     ];

//     register_post_type('location', $args);
// }
// add_action('init', 'register_location_post_type');

// Register custom post type for "loan_item"
function register_loan_item_post_type() {
    $labels = [
        'name' => 'Loan Items',
        'singular_name' => 'Loan Item',
        'menu_name' => 'Loan Items',
        'name_admin_bar' => 'Loan Item',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Loan Item',
        'new_item' => 'New Loan Item',
        'edit_item' => 'Edit Loan Item',
        'view_item' => 'View Loan Item',
        'all_items' => 'All Loan Items',
        'search_items' => 'Search Loan Items',
        'not_found' => 'No loan items found.',
        'not_found_in_trash' => 'No loan items found in Trash.',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'loan-items'],
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'capability_type' => 'post',
    ];

    register_post_type('loan_item', $args);
}
add_action('init', 'register_loan_item_post_type');

function gs_update_loaner_info_ajax() {
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error([
            'message' => 'You are not allowed to do this.',
        ], 403);
    }

    check_ajax_referer('gs_update_loaner_info', 'nonce');

    $loaner_id = isset($_POST['loaner_id']) ? (int) $_POST['loaner_id'] : 0;
    $info = isset($_POST['info']) ? sanitize_textarea_field(wp_unslash($_POST['info'])) : '';

    if ($loaner_id <= 0 || get_post_type($loaner_id) !== 'loaner') {
        wp_send_json_error([
            'message' => 'Invalid loaner selected.',
        ], 400);
    }

    if (function_exists('update_field')) {
        update_field('info', $info, $loaner_id);
    } else {
        update_post_meta($loaner_id, 'info', $info);
    }

    wp_send_json_success([
        'message' => 'Loaner info saved.',
        'info' => $info,
    ]);
}
add_action('wp_ajax_gs_update_loaner_info', 'gs_update_loaner_info_ajax');

function gs_create_loaner_ajax() {
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error([
            'message' => 'You are not allowed to do this.',
        ], 403);
    }

    check_ajax_referer('gs_create_loaner', 'nonce');

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $info = isset($_POST['info']) ? sanitize_textarea_field(wp_unslash($_POST['info'])) : '';

    if ($name === '') {
        wp_send_json_error([
            'message' => 'Loaner name is required.',
        ], 400);
    }

    $loaner_id = wp_insert_post([
        'post_type'   => 'loaner',
        'post_status' => 'publish',
        'post_title'  => $name,
    ]);

    if (is_wp_error($loaner_id) || !$loaner_id) {
        wp_send_json_error([
            'message' => 'Could not create loaner.',
        ], 500);
    }

    if (function_exists('update_field')) {
        update_field('info', $info, $loaner_id);
    } else {
        update_post_meta($loaner_id, 'info', $info);
    }

    wp_send_json_success([
        'message' => 'Loaner created.',
        'loaner' => [
            'id' => $loaner_id,
            'title' => get_the_title($loaner_id),
            'info' => $info,
        ],
    ]);
}
add_action('wp_ajax_gs_create_loaner', 'gs_create_loaner_ajax');
