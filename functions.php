<?php
// register custom post type for "item" 

function enqueue_style(){
    wp_enqueue_style('gs-inventory-style', get_template_directory_uri() . '/style.css');
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
        'taxonomies' => ['post_tag'], // Add default taxonomies if needed
    ];

    register_post_type('item', $args);
}
add_action('init', 'register_item_post_type');

// // Register custom post type for "loaner"
// function register_loaner_post_type() {
//     $labels = [
//         'name' => 'Loaners',
//         'singular_name' => 'Loaner',
//         'menu_name' => 'Loaners',
//         'name_admin_bar' => 'Loaner',
//         'add_new' => 'Add New',
//         'add_new_item' => 'Add New Loaner',
//         'new_item' => 'New Loaner',
//         'edit_item' => 'Edit Loaner',
//         'view_item' => 'View Loaner',
//         'all_items' => 'All Loaners',
//         'search_items' => 'Search Loaners',
//         'not_found' => 'No loaners found.',
//         'not_found_in_trash' => 'No loaners found in Trash.',
//     ];

//     $args = [
//         'labels' => $labels,
//         'public' => true,
//         'has_archive' => true,
//         'rewrite' => ['slug' => 'loaners'],
//         'menu_icon' => 'dashicons-groups', // You can choose another icon if you prefer
//         'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
//         'show_in_rest' => true,
//     ];

//     register_post_type('loaner', $args);
// }
// add_action('init', 'register_loaner_post_type');

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
