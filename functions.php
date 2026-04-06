<?php
// register custom post type for "item" 

function gs_asset_version(string $relative_path): ?string {
    $asset_path = get_template_directory() . '/' . ltrim($relative_path, '/');

    if (!file_exists($asset_path)) {
        return null;
    }

    $hash = md5_file($asset_path);

    if ($hash !== false) {
        return $hash;
    }

    return (string) filemtime($asset_path);
}

function gs_get_item_availability_pill_text(int $item_id): string {
    if ($item_id <= 0) {
        return '0/0';
    }

    static $loaned_quantities = null;

    if (!is_array($loaned_quantities)) {
        if (!function_exists('gs_get_loaned_quantities_map')) {
            require_once get_template_directory() . '/inc/loans/loan-queries.php';
        }

        $loaned_quantities = function_exists('gs_get_loaned_quantities_map')
            ? gs_get_loaned_quantities_map()
            : [];
    }

    $stock_total = (int) get_field('stock_total', $item_id);
    $loaned = (int) ($loaned_quantities[$item_id] ?? 0);
    $available = max(0, $stock_total - $loaned);

    return sprintf('%d/%d', $available, $stock_total);
}

function enqueue_style(){
    $style_version = gs_asset_version('style.css');

    wp_enqueue_style(
        'gs-inventory-style',
        get_template_directory_uri() . '/style.css',
        [],
        $style_version
    );

    $zoom_script_version = gs_asset_version('assets/js/item-image-zoom.js');

    wp_enqueue_script(
        'gs-item-image-zoom',
        get_template_directory_uri() . '/assets/js/item-image-zoom.js',
        [],
        $zoom_script_version,
        true
    );

    $scripts = [
        'gs-nav'              => 'assets/js/nav.js',
        'gs-filter-input'     => 'assets/js/filter-input.js',
        'gs-filter-tags'      => 'assets/js/filter-tags.js',
        'gs-item-modal'       => 'assets/js/item-modal.js',
        'gs-item-list'        => 'assets/js/item-list.js',
        'gs-loan-item-list'   => 'assets/js/loan-item-list.js',
        'gs-loaner-picker'    => 'assets/js/loaner-picker.js',
        'gs-loan-form'        => 'assets/js/loan-form.js',
        'gs-loan-single-card' => 'assets/js/loan-single-card.js',
    ];

    foreach ($scripts as $handle => $path) {
        wp_enqueue_script($handle, get_template_directory_uri() . '/' . $path, [], gs_asset_version($path), true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_style');

function gs_register_theme_menus() {
    add_theme_support('post-thumbnails');

    // Item detail images: generate mobile and desktop-friendly variants.
    add_image_size('gs-item-mobile', 640, 640, false);
    add_image_size('gs-item-detail', 1200, 1200, false);

    register_nav_menus([
        'main-menu' => 'Main Menu',
    ]);
}
add_action('after_setup_theme', 'gs_register_theme_menus');

// Hide the frontend admin toolbar for all logged-in users.
add_filter('show_admin_bar', '__return_false');

function gs_login_rate_limit_window_seconds(): int {
    return 15 * MINUTE_IN_SECONDS;
}

function gs_login_rate_limit_max_attempts(): int {
    return 5;
}

function gs_login_rate_limit_client_ip(): string {
    $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
    $client_ip = filter_var($remote_addr, FILTER_VALIDATE_IP);

    return $client_ip ? $client_ip : 'unknown';
}

function gs_login_rate_limit_key(string $username, string $ip): string {
    return 'gs_login_attempts_' . md5(strtolower($username) . '|' . $ip);
}

function gs_login_rate_limit_normalize_username(string $username): string {
    $normalized = sanitize_user($username, true);
    return $normalized !== '' ? $normalized : 'unknown';
}

function gs_login_rate_limit_authenticate($user, $username, $password) {
    if ($user instanceof WP_User) {
        return $user;
    }

    $normalized_username = gs_login_rate_limit_normalize_username((string) $username);
    $ip = gs_login_rate_limit_client_ip();
    $attempt_key = gs_login_rate_limit_key($normalized_username, $ip);
    $attempt_count = (int) get_transient($attempt_key);

    if ($attempt_count < gs_login_rate_limit_max_attempts()) {
        return $user;
    }

    return new WP_Error(
        'too_many_login_attempts',
        sprintf(
            'Too many login attempts. Please wait %d minutes and try again.',
            (int) ceil(gs_login_rate_limit_window_seconds() / MINUTE_IN_SECONDS)
        )
    );
}
add_filter('authenticate', 'gs_login_rate_limit_authenticate', 30, 3);

function gs_login_rate_limit_record_failure(string $username): void {
    $normalized_username = gs_login_rate_limit_normalize_username($username);
    $ip = gs_login_rate_limit_client_ip();
    $attempt_key = gs_login_rate_limit_key($normalized_username, $ip);
    $attempt_count = (int) get_transient($attempt_key);

    set_transient($attempt_key, $attempt_count + 1, gs_login_rate_limit_window_seconds());
}
add_action('wp_login_failed', 'gs_login_rate_limit_record_failure');

function gs_login_rate_limit_clear_on_success(string $user_login, WP_User $user): void {
    $normalized_username = gs_login_rate_limit_normalize_username($user_login);
    $ip = gs_login_rate_limit_client_ip();
    $attempt_key = gs_login_rate_limit_key($normalized_username, $ip);

    delete_transient($attempt_key);
}
add_action('wp_login', 'gs_login_rate_limit_clear_on_success', 10, 2);

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

function gs_use_single_loaner_template($template) {
    if (!is_singular('loaner')) {
        return $template;
    }

    $loaner_template = locate_template('single-loaner.php');

    if ($loaner_template) {
        return $loaner_template;
    }

    return $template;
}
add_filter('template_include', 'gs_use_single_loaner_template');

function gs_flush_rewrites_on_theme_switch() {
    register_item_post_type();
    register_item_taxonomies();
    register_item_condition_taxonomy();
    register_storage_locations_taxonomy();
    register_loaner_post_type();
    register_loan_post_type();
    register_loan_item_post_type();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'gs_flush_rewrites_on_theme_switch');

function gs_maybe_flush_rewrites_once() {
    if (get_option('gs_inventory_rewrites_flushed') === '1') {
        return;
    }

    register_item_post_type();
    register_item_taxonomies();
    register_item_condition_taxonomy();
    register_storage_locations_taxonomy();
    register_loaner_post_type();
    register_loan_post_type();
    register_loan_item_post_type();

    flush_rewrite_rules(false);
    update_option('gs_inventory_rewrites_flushed', '1', false);
}
add_action('admin_init', 'gs_maybe_flush_rewrites_once');
