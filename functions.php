<?php
// register custom post type for "item"

function gs_asset_version( string $relative_path ): ?string {
	$asset_path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

	if ( ! file_exists( $asset_path ) ) {
		return null;
	}

	$hash = md5_file( $asset_path );

	if ( $hash !== false ) {
		return $hash;
	}

	return (string) filemtime( $asset_path );
}

function gs_get_item_availability_pill_text( int $item_id ): string {
	if ( $item_id <= 0 ) {
		return '0/0';
	}

	static $loaned_quantities = null;

	if ( ! is_array( $loaned_quantities ) ) {
		if ( ! function_exists( 'gs_get_loaned_quantities_map' ) ) {
			require_once get_template_directory() . '/inc/loans/loan-queries.php';
		}

		$loaned_quantities = function_exists( 'gs_get_loaned_quantities_map' )
			? gs_get_loaned_quantities_map()
			: array();
	}

	$stock_total = (int) get_field( 'stock_total', $item_id );
	$loaned      = (int) ( $loaned_quantities[ $item_id ] ?? 0 );
	$available   = max( 0, $stock_total - $loaned );

	return sprintf( '%d/%d', $available, $stock_total );
}

function gs_get_item_modal_dialog_html( int $item_id ): string {
	ob_start();
	get_template_part( 'template-parts/items/item-info', 'modal-dialog', array( 'item_id' => $item_id ) );
	return (string) ob_get_clean();
}

function gs_ajax_get_item_modal_html(): void {
	$nonce = isset( $_POST['nonce'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['nonce'] ) )
		: '';

	if ( ! wp_verify_nonce( $nonce, 'gs_item_modal_html' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid request.' ), 403 );
	}

	$item_id = isset( $_POST['item_id'] )
		? (int) $_POST['item_id']
		: 0;

	if ( $item_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'Missing item id.' ), 400 );
	}

	$item = get_post( $item_id );
	if ( ! $item || 'item' !== $item->post_type || 'publish' !== $item->post_status ) {
		wp_send_json_error( array( 'message' => 'Item not found.' ), 404 );
	}

	wp_send_json_success(
		array(
			'html' => gs_get_item_modal_dialog_html( $item_id ),
		)
	);
}
add_action( 'wp_ajax_gs_get_item_modal_html', 'gs_ajax_get_item_modal_html' );
add_action( 'wp_ajax_nopriv_gs_get_item_modal_html', 'gs_ajax_get_item_modal_html' );

function enqueue_style() {
	$style_version = gs_asset_version( 'style.css' );

	wp_enqueue_style(
		'gs-inventory-style',
		get_template_directory_uri() . '/style.css',
		array(),
		$style_version
	);

	$zoom_script_version = gs_asset_version( 'assets/js/item-image-zoom.js' );

	wp_enqueue_script(
		'gs-item-image-zoom',
		get_template_directory_uri() . '/assets/js/item-image-zoom.js',
		array(),
		$zoom_script_version,
		true
	);

	$scripts = array(
		'gs-nav'              => 'assets/js/nav.js',
		'gs-filter-input'     => 'assets/js/filter-input.js',
		'gs-filter-tags'      => 'assets/js/filter-tags.js',
		'gs-item-modal'       => 'assets/js/item-modal.js',
		'gs-item-list'        => 'assets/js/item-list.js',
		'gs-loan-item-list'   => 'assets/js/loan-item-list.js',
		'gs-loaner-picker'    => 'assets/js/loaner-picker.js',
		'gs-loan-form'        => 'assets/js/loan-form.js',
		'gs-loan-single-card' => 'assets/js/loan-single-card.js',
	);

	foreach ( $scripts as $handle => $path ) {
		wp_enqueue_script( $handle, get_template_directory_uri() . '/' . $path, array(), gs_asset_version( $path ), true );
	}

	wp_localize_script(
		'gs-item-modal',
		'gsItemModalConfig',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gs_item_modal_html' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'enqueue_style' );

function gs_register_theme_menus() {
	add_theme_support( 'post-thumbnails' );

	// Item detail images: generate mobile and desktop-friendly variants.
	add_image_size( 'gs-item-mobile', 640, 640, false );
	add_image_size( 'gs-item-detail', 1200, 1200, false );

	register_nav_menus(
		array(
			'main-menu' => 'Main Menu',
		)
	);
}
add_action( 'after_setup_theme', 'gs_register_theme_menus' );

// Hide the frontend admin toolbar for all logged-in users.
add_filter( 'show_admin_bar', '__return_false' );

function gs_login_rate_limit_window_seconds(): int {
	return 15 * MINUTE_IN_SECONDS;
}

function gs_login_rate_limit_max_attempts(): int {
	return 5;
}

function gs_login_rate_limit_client_ip(): string {
	$remote_addr = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
		: '';
	$client_ip   = filter_var( $remote_addr, FILTER_VALIDATE_IP );

	return $client_ip ? $client_ip : 'unknown';
}

function gs_can_manage_inventory(): bool {
	return is_user_logged_in() && current_user_can( 'edit_posts' );
}

function gs_login_rate_limit_key( string $username, string $ip ): string {
	return 'gs_login_attempts_' . md5( strtolower( $username ) . '|' . $ip );
}

function gs_login_rate_limit_normalize_username( string $username ): string {
	$normalized = sanitize_user( $username, true );
	return $normalized !== '' ? $normalized : 'unknown';
}

function gs_login_rate_limit_authenticate( $user, $username, $password ) {
	if ( $user instanceof WP_User ) {
		return $user;
	}

	$normalized_username = gs_login_rate_limit_normalize_username( (string) $username );
	$ip                  = gs_login_rate_limit_client_ip();
	$attempt_key         = gs_login_rate_limit_key( $normalized_username, $ip );
	$attempt_count       = (int) get_transient( $attempt_key );

	if ( $attempt_count < gs_login_rate_limit_max_attempts() ) {
		return $user;
	}

	return new WP_Error(
		'too_many_login_attempts',
		sprintf(
			'Too many login attempts. Please wait %d minutes and try again.',
			(int) ceil( gs_login_rate_limit_window_seconds() / MINUTE_IN_SECONDS )
		)
	);
}
add_filter( 'authenticate', 'gs_login_rate_limit_authenticate', 30, 3 );

function gs_login_rate_limit_record_failure( string $username ): void {
	$normalized_username = gs_login_rate_limit_normalize_username( $username );
	$ip                  = gs_login_rate_limit_client_ip();
	$attempt_key         = gs_login_rate_limit_key( $normalized_username, $ip );
	$attempt_count       = (int) get_transient( $attempt_key );

	set_transient( $attempt_key, $attempt_count + 1, gs_login_rate_limit_window_seconds() );
}
add_action( 'wp_login_failed', 'gs_login_rate_limit_record_failure' );

function gs_login_rate_limit_clear_on_success( string $user_login, WP_User $user ): void {
	$normalized_username = gs_login_rate_limit_normalize_username( $user_login );
	$ip                  = gs_login_rate_limit_client_ip();
	$attempt_key         = gs_login_rate_limit_key( $normalized_username, $ip );

	delete_transient( $attempt_key );
}
add_action( 'wp_login', 'gs_login_rate_limit_clear_on_success', 10, 2 );

function register_item_post_type() {
	$labels = array(
		'name'               => 'Items',
		'singular_name'      => 'Item',
		'menu_name'          => 'Items',
		'name_admin_bar'     => 'Item',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Item',
		'new_item'           => 'New Item',
		'edit_item'          => 'Edit Item',
		'view_item'          => 'View Item',
		'all_items'          => 'All Items',
		'search_items'       => 'Search Items',
		'not_found'          => 'No items found.',
		'not_found_in_trash' => 'No items found in Trash.',
	);

	$args = array(
		'labels'          => $labels,
		'public'          => true,
		'has_archive'     => true,
		'rewrite'         => array( 'slug' => 'items' ),
		'menu_icon'       => 'dashicons-archive', // You can choose another icon if you prefer
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		'show_in_rest'    => true,
		'capability_type' => 'post', // Use 'post' capabilities
		'taxonomies'      => array( 'item_tag', 'item_condition', 'storage_locations' ),
	);

	register_post_type( 'item', $args );
}
add_action( 'init', 'register_item_post_type' );

function register_item_taxonomies() {
	$labels = array(
		'name'                       => 'Item Tags',
		'singular_name'              => 'Item Tag',
		'search_items'               => 'Search Item Tags',
		'popular_items'              => 'Popular Item Tags',
		'all_items'                  => 'All Item Tags',
		'edit_item'                  => 'Edit Item Tag',
		'update_item'                => 'Update Item Tag',
		'add_new_item'               => 'Add New Item Tag',
		'new_item_name'              => 'New Item Tag Name',
		'separate_items_with_commas' => 'Separate item tags with commas',
		'add_or_remove_items'        => 'Add or remove item tags',
		'choose_from_most_used'      => 'Choose from the most used item tags',
		'menu_name'                  => 'Item Tags',
	);

	$args = array(
		'hierarchical'          => false,
		'labels'                => $labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'item-tag' ),
		'show_in_rest'          => true,
	);

	register_taxonomy( 'item_tag', array( 'item' ), $args );
}
add_action( 'init', 'register_item_taxonomies' );

function register_item_condition_taxonomy() {
	$labels = array(
		'name'              => 'Item Conditions',
		'singular_name'     => 'Item Condition',
		'search_items'      => 'Search Item Conditions',
		'all_items'         => 'All Item Conditions',
		'parent_item'       => 'Parent Item Condition',
		'parent_item_colon' => 'Parent Item Condition:',
		'edit_item'         => 'Edit Item Condition',
		'update_item'       => 'Update Item Condition',
		'add_new_item'      => 'Add New Item Condition',
		'new_item_name'     => 'New Item Condition Name',
		'menu_name'         => 'Item Conditions',
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'item-condition' ),
		'show_in_rest'      => true,
	);

	register_taxonomy( 'item_condition', array( 'item' ), $args );
}
add_action( 'init', 'register_item_condition_taxonomy' );

function register_storage_locations_taxonomy() {
	$labels = array(
		'name'              => 'Storage Locations',
		'singular_name'     => 'Storage Location',
		'search_items'      => 'Search Storage Locations',
		'all_items'         => 'All Storage Locations',
		'parent_item'       => 'Parent Storage Location',
		'parent_item_colon' => 'Parent Storage Location:',
		'edit_item'         => 'Edit Storage Location',
		'update_item'       => 'Update Storage Location',
		'add_new_item'      => 'Add New Storage Location',
		'new_item_name'     => 'New Storage Location Name',
		'menu_name'         => 'Storage Locations',
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'storage-location' ),
		'show_in_rest'      => true,
	);

	register_taxonomy( 'storage_locations', array( 'item' ), $args );
}
add_action( 'init', 'register_storage_locations_taxonomy' );

// Register custom post type for "loaner"
function register_loaner_post_type() {
	$labels = array(
		'name'               => 'Loaners',
		'singular_name'      => 'Loaner',
		'menu_name'          => 'Loaners',
		'name_admin_bar'     => 'Loaner',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Loaner',
		'new_item'           => 'New Loaner',
		'edit_item'          => 'Edit Loaner',
		'view_item'          => 'View Loaner',
		'all_items'          => 'All Loaners',
		'search_items'       => 'Search Loaners',
		'not_found'          => 'No loaners found.',
		'not_found_in_trash' => 'No loaners found in Trash.',
	);

	$args = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'loaners' ),
		'menu_icon'    => 'dashicons-groups', // You can choose another icon if you prefer
		'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'show_in_rest' => true,
	);

	register_post_type( 'loaner', $args );
}
add_action( 'init', 'register_loaner_post_type' );

// register custom post type for "loan"
function register_loan_post_type() {
	$labels = array(
		'name'               => 'Loans',
		'singular_name'      => 'Loan',
		'menu_name'          => 'Loans',
		'name_admin_bar'     => 'Loan',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Loan',
		'new_item'           => 'New Loan',
		'edit_item'          => 'Edit Loan',
		'view_item'          => 'View Loan',
		'all_items'          => 'All Loans',
		'search_items'       => 'Search Loans',
		'not_found'          => 'No loans found.',
		'not_found_in_trash' => 'No loans found in Trash.',
	);

	$args = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'loans' ),
		'menu_icon'    => 'dashicons-clipboard', // You can choose another icon if you prefer
		'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'show_in_rest' => true,
	);

	register_post_type( 'loan', $args );
}
add_action( 'init', 'register_loan_post_type' );

// // Register custom post type for "location"
// function register_location_post_type() {
// $labels = [
// 'name' => 'Locations',
// 'singular_name' => 'Location',
// 'menu_name' => 'Locations',
// 'name_admin_bar' => 'Location',
// 'add_new' => 'Add New',
// 'add_new_item' => 'Add New Location',
// 'new_item' => 'New Location',
// 'edit_item' => 'Edit Location',
// 'view_item' => 'View Location',
// 'all_items' => 'All Locations',
// 'search_items' => 'Search Locations',
// 'not_found' => 'No locations found.',
// 'not_found_in_trash' => 'No locations found in Trash.',
// ];

// $args = [
// 'labels' => $labels,
// 'public' => true,
// 'has_archive' => true,
// 'rewrite' => ['slug' => 'locations'],
// 'menu_icon' => 'dashicons-location',
// 'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
// 'show_in_rest' => true,
// 'capability_type' => 'post',
// ];

// register_post_type('location', $args);
// }
// add_action('init', 'register_location_post_type');

// Register custom post type for "loan_item"
function register_loan_item_post_type() {
	$labels = array(
		'name'               => 'Loan Items',
		'singular_name'      => 'Loan Item',
		'menu_name'          => 'Loan Items',
		'name_admin_bar'     => 'Loan Item',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Loan Item',
		'new_item'           => 'New Loan Item',
		'edit_item'          => 'Edit Loan Item',
		'view_item'          => 'View Loan Item',
		'all_items'          => 'All Loan Items',
		'search_items'       => 'Search Loan Items',
		'not_found'          => 'No loan items found.',
		'not_found_in_trash' => 'No loan items found in Trash.',
	);

	$args = array(
		'labels'          => $labels,
		'public'          => true,
		'has_archive'     => true,
		'rewrite'         => array( 'slug' => 'loan-items' ),
		'menu_icon'       => 'dashicons-cart',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'show_in_rest'    => true,
		'capability_type' => 'post',
	);

	register_post_type( 'loan_item', $args );
}
add_action( 'init', 'register_loan_item_post_type' );

function gs_update_loaner_info_ajax() {
	if ( ! gs_can_manage_inventory() ) {
		wp_send_json_error(
			array(
				'message' => 'You are not allowed to do this.',
			),
			403
		);
	}

	check_ajax_referer( 'gs_update_loaner_info', 'nonce' );

	$loaner_id = isset( $_POST['loaner_id'] ) ? (int) $_POST['loaner_id'] : 0;
	$info      = isset( $_POST['info'] ) ? sanitize_textarea_field( wp_unslash( $_POST['info'] ) ) : '';

	if ( $loaner_id <= 0 || get_post_type( $loaner_id ) !== 'loaner' ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid loaner selected.',
			),
			400
		);
	}

	if ( function_exists( 'update_field' ) ) {
		update_field( 'info', $info, $loaner_id );
	} else {
		update_post_meta( $loaner_id, 'info', $info );
	}

	wp_send_json_success(
		array(
			'message' => 'Loaner info saved.',
			'info'    => $info,
		)
	);
}
add_action( 'wp_ajax_gs_update_loaner_info', 'gs_update_loaner_info_ajax' );

function gs_create_loaner_ajax() {
	if ( ! gs_can_manage_inventory() ) {
		wp_send_json_error(
			array(
				'message' => 'You are not allowed to do this.',
			),
			403
		);
	}

	check_ajax_referer( 'gs_create_loaner', 'nonce' );

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$info = isset( $_POST['info'] ) ? sanitize_textarea_field( wp_unslash( $_POST['info'] ) ) : '';

	if ( $name === '' ) {
		wp_send_json_error(
			array(
				'message' => 'Loaner name is required.',
			),
			400
		);
	}

	$loaner_id = wp_insert_post(
		array(
			'post_type'   => 'loaner',
			'post_status' => 'publish',
			'post_title'  => $name,
		)
	);

	if ( is_wp_error( $loaner_id ) || ! $loaner_id ) {
		wp_send_json_error(
			array(
				'message' => 'Could not create loaner.',
			),
			500
		);
	}

	if ( function_exists( 'update_field' ) ) {
		update_field( 'info', $info, $loaner_id );
	} else {
		update_post_meta( $loaner_id, 'info', $info );
	}

	wp_send_json_success(
		array(
			'message' => 'Loaner created.',
			'loaner'  => array(
				'id'    => $loaner_id,
				'title' => get_the_title( $loaner_id ),
				'info'  => $info,
			),
		)
	);
}
add_action( 'wp_ajax_gs_create_loaner', 'gs_create_loaner_ajax' );

function gs_use_single_loaner_template( $template ) {
	if ( ! is_singular( 'loaner' ) ) {
		return $template;
	}

	$loaner_template = locate_template( 'single-loaner.php' );

	if ( $loaner_template ) {
		return $loaner_template;
	}

	return $template;
}
add_filter( 'template_include', 'gs_use_single_loaner_template' );

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
add_action( 'after_switch_theme', 'gs_flush_rewrites_on_theme_switch' );

function gs_maybe_flush_rewrites_once() {
	if ( get_option( 'gs_inventory_rewrites_flushed' ) === '1' ) {
		return;
	}

	register_item_post_type();
	register_item_taxonomies();
	register_item_condition_taxonomy();
	register_storage_locations_taxonomy();
	register_loaner_post_type();
	register_loan_post_type();
	register_loan_item_post_type();

	flush_rewrite_rules( false );
	update_option( 'gs_inventory_rewrites_flushed', '1', false );
}
add_action( 'admin_init', 'gs_maybe_flush_rewrites_once' );

function gs_theme_setup_page_slug(): string {
	return 'gs-theme-setup';
}

function gs_theme_setup_required_pages(): array {
	return array(
		'create-loan' => array(
			'title'    => 'Create Loan',
			'template' => 'page-create-loan.php',
		),
		'item-overview' => array(
			'title'    => 'Item Overview',
			'template' => 'page-item-overview.php',
		),
		'loan-overview' => array(
			'title'    => 'Loan Overview',
			'template' => 'page-loan-overview.php',
		),
		'loaner-overview' => array(
			'title'    => 'Loaner Overview',
			'template' => 'page-loaner-overview.php',
		),
	);
}

function gs_theme_setup_find_page_by_slug( string $slug ): ?WP_Post {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page' => 1,
		)
	);

	if ( empty( $pages ) || ! $pages[0] instanceof WP_Post ) {
		return null;
	}

	return $pages[0];
}

function gs_theme_setup_ensure_page( string $slug, array $config, array &$report ): int {
	$title    = (string) ( $config['title'] ?? $slug );
	$template = (string) ( $config['template'] ?? '' );
	$page     = gs_theme_setup_find_page_by_slug( $slug );

	if ( $page ) {
		$page_id = (int) $page->ID;
		$report[] = sprintf( 'Page found: %s (%d)', $title, $page_id );
	} else {
		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => $slug,
			)
		);

		if ( is_wp_error( $page_id ) || $page_id <= 0 ) {
			$report[] = sprintf( 'Page create failed: %s', $title );
			return 0;
		}

		$report[] = sprintf( 'Page created: %s (%d)', $title, $page_id );
	}

	if ( $template !== '' ) {
		update_post_meta( $page_id, '_wp_page_template', $template );
		$report[] = sprintf( 'Template set: %s -> %s', $title, $template );
	}

	return (int) $page_id;
}

function gs_theme_setup_ensure_main_menu( array $page_ids, array &$report ): int {
	$menu_name = 'Main Menu';
	$menu_obj  = wp_get_nav_menu_object( $menu_name );
	$menu_id   = $menu_obj ? (int) $menu_obj->term_id : 0;

	if ( $menu_id <= 0 ) {
		$menu_id = (int) wp_create_nav_menu( $menu_name );
		$report[] = sprintf( 'Menu created: %s (%d)', $menu_name, $menu_id );
	} else {
		$report[] = sprintf( 'Menu found: %s (%d)', $menu_name, $menu_id );
	}

	if ( $menu_id <= 0 ) {
		$report[] = 'Menu setup failed: could not create or load main menu.';
		return 0;
	}

	$menu_items = wp_get_nav_menu_items( $menu_id, array( 'post_status' => 'any' ) );
	$menu_items = is_array( $menu_items ) ? $menu_items : array();

	$by_object_id = array();
	foreach ( $menu_items as $menu_item ) {
		$object_id = (int) ( $menu_item->object_id ?? 0 );
		if ( $object_id > 0 ) {
			$by_object_id[ $object_id ] = (int) $menu_item->ID;
		}
	}

	$position = 1;
	foreach ( $page_ids as $page_id ) {
		$page_id = (int) $page_id;
		if ( $page_id <= 0 ) {
			continue;
		}

		$item_id = (int) ( $by_object_id[ $page_id ] ?? 0 );
		$args    = array(
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position,
		);

		if ( $item_id > 0 ) {
			wp_update_nav_menu_item( $menu_id, $item_id, $args );
			$report[] = sprintf( 'Menu item updated: page %d position %d', $page_id, $position );
		} else {
			wp_update_nav_menu_item( $menu_id, 0, $args );
			$report[] = sprintf( 'Menu item added: page %d position %d', $page_id, $position );
		}

		$position++;
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( is_array( $locations ) && array_key_exists( 'main-menu', $locations ) ) {
		$locations['main-menu'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		$report[] = 'Menu location assigned: main-menu.';
	} else {
		$report[] = 'Menu location skipped: main-menu location not available.';
	}

	return $menu_id;
}

function gs_theme_setup_ensure_item_conditions( array &$report ): void {
	$terms = array(
		'broken' => 'broken',
		'new'    => 'new',
		'worn'   => 'worn',
	);

	foreach ( $terms as $slug => $name ) {
		$existing = term_exists( $slug, 'item_condition' );
		if ( $existing ) {
			$report[] = sprintf( 'Term found: %s', $name );
			continue;
		}

		$result = wp_insert_term(
			$name,
			'item_condition',
			array(
				'slug' => $slug,
			)
		);

		if ( is_wp_error( $result ) ) {
			$report[] = sprintf( 'Term create failed: %s', $name );
			continue;
		}

		$report[] = sprintf( 'Term created: %s', $name );
	}
}

function gs_theme_setup_apply_site_settings( int $front_page_id, array &$report ): void {
	update_option( 'permalink_structure', '/%postname%/' );
	$report[] = 'Permalink structure set: /%postname%/';

	if ( $front_page_id > 0 ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id );
		$report[] = sprintf( 'Reading settings set: static front page (%d).', $front_page_id );
	} else {
		$report[] = 'Reading settings skipped: front page not available.';
	}

	flush_rewrite_rules( false );
	$report[] = 'Rewrite rules flushed.';
}

function gs_run_theme_initializer(): array {
	$report         = array();
	$page_configs   = gs_theme_setup_required_pages();
	$ordered_page_ids = array();

	foreach ( $page_configs as $slug => $config ) {
		$page_id = gs_theme_setup_ensure_page( $slug, $config, $report );
		if ( $page_id > 0 ) {
			$ordered_page_ids[ $slug ] = $page_id;
		}
	}

	gs_theme_setup_ensure_main_menu( array_values( $ordered_page_ids ), $report );
	gs_theme_setup_ensure_item_conditions( $report );

	$front_page_id = (int) ( $ordered_page_ids['create-loan'] ?? 0 );
	gs_theme_setup_apply_site_settings( $front_page_id, $report );

	return $report;
}

function gs_register_theme_setup_admin_page(): void {
	add_theme_page(
		'Theme Setup',
		'Theme Setup',
		'edit_theme_options',
		gs_theme_setup_page_slug(),
		'gs_render_theme_setup_admin_page'
	);
}
add_action( 'admin_menu', 'gs_register_theme_setup_admin_page' );

function gs_enqueue_theme_setup_assets( string $hook_suffix ): void {
	if ( 'appearance_page_' . gs_theme_setup_page_slug() !== $hook_suffix ) {
		return;
	}

	add_thickbox();
}
add_action( 'admin_enqueue_scripts', 'gs_enqueue_theme_setup_assets' );

function gs_get_theme_setup_report_transient_key( int $user_id ): string {
	return 'gs_theme_setup_report_' . $user_id;
}

function gs_theme_setup_plugin_shortcuts(): array {
	$plugins = array(
		array(
			'slug'  => 'advanced-custom-fields',
			'label' => 'Advanced Custom Fields',
			'description' => 'Provides the custom fields framework used across this website for structured content like items, loans, and loaners.',
		),
		array(
			'slug'  => 'deployer-for-git',
			'label' => 'Deployer for Git',
			'description' => 'Use the following info for the "Install Theme Setup" inside the plugin to subscribe to theme updates.',
			'details' => array(
				'Provider Type'  => 'GitHub',
				'Repository URL' => 'https://github.com/korrecktfort/gs-inventory',
				'Branch'         => 'main',
			),
		),
	);

	return apply_filters( 'gs_theme_setup_plugin_shortcuts', $plugins );
}

function gs_render_theme_setup_admin_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'You are not allowed to access this page.' );
	}

	$user_id = get_current_user_id();
	$report  = get_transient( gs_get_theme_setup_report_transient_key( $user_id ) );
	$report  = is_array( $report ) ? $report : array();

	if ( isset( $_GET['gs_setup_done'] ) ) {
		delete_transient( gs_get_theme_setup_report_transient_key( $user_id ) );
	}
	?>
	<div class="wrap">
		<h1>Theme Setup</h1>
		<p>Run one-click initialization for required pages, menu, taxonomy terms, permalinks, and reading settings.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gs_run_theme_setup">
			<?php wp_nonce_field( 'gs_run_theme_setup', 'gs_theme_setup_nonce' ); ?>
			<?php submit_button( 'Run Theme Initialization', 'primary', 'submit', false ); ?>
		</form>

		<?php $plugin_shortcuts = gs_theme_setup_plugin_shortcuts(); ?>
		<?php if ( ! empty( $plugin_shortcuts ) ) : ?>
			<h2>Plugin Shortcuts</h2>
			<p>The following plugins are needed for the functionality of this website.</p>
			<p>Use the buttons below to open each plugin page directly in wp-admin.</p>
			<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));max-width:1100px;">
				<?php foreach ( $plugin_shortcuts as $plugin ) : ?>
					<?php
					$plugin_slug = sanitize_key( (string) ( $plugin['slug'] ?? '' ) );
					$plugin_label = trim( (string) ( $plugin['label'] ?? $plugin_slug ) );
					$plugin_description = trim( (string) ( $plugin['description'] ?? '' ) );
					$plugin_details = is_array( $plugin['details'] ?? null ) ? $plugin['details'] : array();

					if ( '' === $plugin_slug || '' === $plugin_label ) {
						continue;
					}

					$plugin_url = add_query_arg(
						array(
							'tab'    => 'plugin-information',
							'plugin' => $plugin_slug,
							'TB_iframe' => 'true',
							'width' => '600',
							'height' => '550',
						),
						admin_url( 'plugin-install.php' )
					);
					?>
					<div class="postbox" style="padding:12px;">
						<h3 style="margin:0 0 10px 0;"><?php echo esc_html( $plugin_label ); ?></h3>
						<a class="button button-secondary thickbox" href="<?php echo esc_url( $plugin_url ); ?>">Open Plugin Page</a>

						<p style="margin:10px 0 10px 0;"><?php echo esc_html( $plugin_description ); ?></p>

						<?php if ( ! empty( $plugin_details ) ) : ?>
							<ul style="margin:0 0 0 18px;">
								<?php foreach ( $plugin_details as $detail_key => $detail_value ) : ?>
									<?php
									$detail_label = trim( (string) $detail_key );
									$detail_text = trim( (string) $detail_value );

									if ( '' === $detail_label || '' === $detail_text ) {
										continue;
									}
									?>
									<li><strong><?php echo esc_html( $detail_label ); ?>:</strong> <?php echo esc_html( $detail_text ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $report ) ) : ?>
			<h2>Last Run Report</h2>
			<ul>
				<?php foreach ( $report as $line ) : ?>
					<li><?php echo esc_html( (string) $line ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

function gs_handle_theme_setup_post_action(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'You are not allowed to run theme setup.' );
	}

	$nonce = isset( $_POST['gs_theme_setup_nonce'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['gs_theme_setup_nonce'] ) )
		: '';

	if ( ! wp_verify_nonce( $nonce, 'gs_run_theme_setup' ) ) {
		wp_die( 'Invalid setup request.' );
	}

	$report = gs_run_theme_initializer();
	set_transient( gs_get_theme_setup_report_transient_key( get_current_user_id() ), $report, MINUTE_IN_SECONDS * 30 );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'          => gs_theme_setup_page_slug(),
				'gs_setup_done' => '1',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_gs_run_theme_setup', 'gs_handle_theme_setup_post_action' );
