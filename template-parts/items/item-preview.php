<?php
// Flow: parse input args -> build/normalize preview_data -> optional filter hook -> render HTML.
$args = wp_parse_args(
	$args ?? array(),
	array(
		'item_id'             => 0,
		'mode'                => 'default',
		'root_class'          => '',
		'title_url'           => '',
		'title_modal_trigger' => false,
		'show_title'          => true,
		'show_taxonomies'     => false,
		'show_data_table'     => false,
		'show_image'          => true,
	)
);

$item_id = (int) $args['item_id'];
$root_class = trim( (string) $args['root_class'] );
$title_url = (string) $args['title_url'];
$title_modal_trigger = (bool) $args['title_modal_trigger'];
$show_title = (bool) $args['show_title'];
$show_taxonomies = (bool) $args['show_taxonomies'];
$show_data_table = (bool) $args['show_data_table'];
$show_image = (bool) $args['show_image'];

$is_modal = ( 'modal' === (string) $args['mode'] );
if ( ! $item_id && ! $is_modal ) {
	return;
}

// Tiny helpers keep ACF-to-view-model mapping compact.
$map_term_field = static function ( $field_name, $taxonomy, $post_id ) {
	$terms = array();
	foreach ( (array) get_field( $field_name, $post_id ) as $term_id ) {
		$term = get_term( (int) $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$terms[ $term->term_id ] = array(
			'id'   => (int) $term->term_id,
			'name' => (string) $term->name,
		);
	}

	return array_values( $terms );
};

$extract_term_names = static function ( $terms ) {
	$names = array();
	foreach ( (array) $terms as $term ) {
		$name = trim( (string) ( $term['name'] ?? '' ) );
		if ( $name !== '' ) {
			$names[] = $name;
		}
	}

	return $names;
};

$preview_data = array(
	'item_id'           => $item_id,
	'name'              => '',
	'stock'             => '0/0',
	'condition'         => array(
		'id'   => 0,
		'name' => '',
	),
	'tags'              => array(),
	'storage_locations' => array(),
	'storage_location'  => '',
	'image'             => array(
		'url'    => '',
		'alt'    => '',
		'srcset' => '',
		'sizes'  => '(max-width: 768px) 100vw, 24rem',
	),
);

if ( $item_id > 0 ) {
	$preview_data['name'] = (string) get_the_title( $item_id );

	if ( function_exists( 'gs_get_item_availability_pill_text' ) ) {
		$preview_data['stock'] = (string) gs_get_item_availability_pill_text( $item_id );
	}

	$image_field = (array) get_field( 'image', $item_id );
	$image_id    = (int) ( $image_field['ID'] ?? $image_field['id'] ?? 0 );

	$preview_data['image']['url'] = (string) ( $image_field['url'] ?? '' );
	$preview_data['image']['alt'] = trim( (string) ( $image_field['alt'] ?? '' ) );

	if ( $image_id > 0 ) {
		$preview_data['image']['url'] = (string) ( wp_get_attachment_image_url( $image_id, 'gs-item-detail' ) ?: wp_get_attachment_image_url( $image_id, 'large' ) );
		$preview_data['image']['srcset'] = (string) wp_get_attachment_image_srcset( $image_id, 'gs-item-detail' );

		if ( '' === $preview_data['image']['alt'] ) {
			$preview_data['image']['alt'] = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
		}
	}

	$condition_id = (int) get_field( 'condition', $item_id );
	if ( $condition_id > 0 ) {
		$condition_term = get_term( $condition_id, 'item_condition' );
		if ( $condition_term && ! is_wp_error( $condition_term ) ) {
			$preview_data['condition'] = array(
				'id'   => (int) $condition_term->term_id,
				'name' => (string) $condition_term->name,
			);
		}
	}

	$preview_data['tags']              = $map_term_field( 'tags', 'item_tag', $item_id );
	$preview_data['storage_locations'] = $map_term_field( 'storage_location', 'storage_locations', $item_id );
}

$preview_data = apply_filters( 'gs_item_preview_data', $preview_data, $item_id );

$item_name = (string) ( $preview_data['name'] ?? '' );
$condition_name = (string) ( $preview_data['condition']['name'] ?? '' );
$tag_terms = is_array( $preview_data['tags'] ?? null ) ? $preview_data['tags'] : array();
$tag_names = $extract_term_names( $tag_terms );
$storage_terms = is_array( $preview_data['storage_locations'] ?? null ) ? $preview_data['storage_locations'] : array();
$storage_location = trim( (string) ( $preview_data['storage_location'] ?? '' ) );

if ( '' === $storage_location ) {
	$storage_location = implode( ', ', $extract_term_names( $storage_terms ) );
}

$image_url = (string) ( $preview_data['image']['url'] ?? '' );
$image_alt = trim( (string) ( $preview_data['image']['alt'] ?? '' ) );
$image_srcset = (string) ( $preview_data['image']['srcset'] ?? '' );
$image_sizes = (string) ( $preview_data['image']['sizes'] ?? '(max-width: 768px) 100vw, 24rem' );

if ( '' === $image_alt ) {
	$image_alt = $item_name !== '' ? $item_name : 'Item image';
}

$has_taxonomy_data = ( '' !== $condition_name || ! empty( $tag_names ) || '' !== $storage_location );
$condition_display = ( $is_modal && '' === $condition_name ) ? '-' : $condition_name;
$storage_display = ( $is_modal && '' === $storage_location ) ? '-' : $storage_location;
$tag_display_names = $tag_names;
if ( $is_modal && empty( $tag_display_names ) ) {
	$tag_display_names = array( '-' );
}

$article_class = 'item-preview';
if ( $is_modal ) {
	$article_class .= ' item-preview--modal';
}
if ( '' !== $root_class ) {
	$article_class .= ' ' . $root_class;
}

$stock_label = ( $item_id || $is_modal ) ? (string) ( $preview_data['stock'] ?? '0/0' ) : '0/0';
$has_image = ( $show_image && '' !== $image_url );
?>

<article class="<?php echo esc_attr( $article_class ); ?>">
	<header class="item-preview-head">
		<?php if ( $show_title ) : ?>
			<h2 class="item-preview-title"<?php echo $is_modal ? ' id="item-modal-name" data-item-field="name"' : ''; ?>>
				<?php if ( $title_modal_trigger && $item_id > 0 ) : ?>
					<?php get_template_part( 'template-parts/items/item-info', 'trigger', array( 'item_id' => $item_id ) ); ?>
				<?php elseif ( '' !== $title_url ) : ?>
					<a class="gs-arrow-link" href="<?php echo esc_url( $title_url ); ?>">
						<?php echo esc_html( $item_name ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $item_name ); ?>
				<?php endif; ?>
			</h2>
		<?php endif; ?>

		<?php if ( ! $show_data_table ) : ?>
			<p class="item-preview-stock">
				<span class="item-preview-stock-value"<?php echo $is_modal ? ' id="item-modal-stock" data-item-field="stock"' : ''; ?>>
					<?php echo esc_html( $stock_label ); ?>
				</span>
			</p>
		<?php endif; ?>
	</header>

	<?php if ( $show_data_table || ( $show_taxonomies && ( $has_taxonomy_data || $is_modal ) ) || ( $show_image && ( $has_image || $is_modal ) ) ) : ?>
		<div class="item-preview-data">
			<?php if ( $show_data_table ) : ?>
				<div class="item-preview-data-row">
					<span class="item-preview-data-key">Stock:</span>
					<span class="item-preview-stock-value"<?php echo $is_modal ? ' id="item-modal-stock" data-item-field="stock"' : ''; ?>>
						<?php echo esc_html( $stock_label ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $show_taxonomies && ( '' !== $condition_name || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row"
					<?php echo $is_modal ? 'data-item-field-row="condition"' : ''; ?>
				>
					<span class="item-preview-data-key">Condition:</span>
					<span
						class="item-preview-tag item-preview-condition-pill"
						<?php echo $is_modal ? 'id="item-modal-condition" data-item-field="condition"' : ''; ?>
					>
						<?php echo esc_html( $condition_display ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $show_taxonomies && ( '' !== $storage_location || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row"
					<?php echo $is_modal ? 'data-item-field-row="storage_location"' : ''; ?>
				>
					<span class="item-preview-data-key">Storage location:</span>
					<span
						class="item-preview-tag item-preview-storage-pill"
						<?php echo $is_modal ? 'id="item-modal-storage-location" data-item-field="storage_location"' : ''; ?>
					>
						<?php echo esc_html( $storage_display ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $show_taxonomies && ( ! empty( $tag_names ) || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row"
					<?php echo $is_modal ? 'data-item-field-row="tags"' : ''; ?>
				>
					<span class="item-preview-data-key">Tags:</span>
					<div
						class="item-preview-tag-list"
						aria-label="Item tags"
						<?php echo $is_modal ? 'id="item-modal-tags" data-item-field="tags"' : ''; ?>
					>
						<?php foreach ( $tag_display_names as $tag_name ) : ?>
							<span class="item-preview-tag"><?php echo esc_html( $tag_name ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $show_image && ( $show_data_table || $has_image || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row item-preview-data-row--image"
					<?php echo $is_modal ? 'data-item-field-row="image"' : ''; ?>
				>
					<span class="item-preview-data-key">Image:</span>
					<div
						class="item-preview-image-wrap"
						<?php echo $is_modal ? 'id="item-modal-image-wrap" data-item-field="image-wrap"' : ''; ?>
						<?php echo ( ! $has_image ) ? 'hidden' : ''; ?>
					>
						<img
							class="item-preview-image"
							<?php echo $is_modal ? 'id="item-modal-image" data-item-field="image"' : ''; ?>
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $image_alt ); ?>"
							<?php echo ( '' !== $image_srcset ) ? 'srcset="' . esc_attr( $image_srcset ) . '"' : ''; ?>
							sizes="<?php echo esc_attr( $image_sizes ); ?>"
							loading="lazy"
							decoding="async"
							<?php echo ( ! $has_image ) ? 'hidden' : ''; ?>
						>
					</div>
					<span
						class="item-preview-image-empty"
						<?php echo $is_modal ? 'id="item-modal-image-empty" data-item-field="image-empty"' : ''; ?>
						<?php echo $has_image ? 'hidden' : ''; ?>
					>
						No image
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $is_modal ) : ?>
				<?php do_action( 'gs_item_preview_extra_rows', $item_id, $preview_data, $args ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

</article>