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

// Canonical view model used by the markup section below.
$preview_data = array(
	'item_id'           => $item_id,
	'name'              => $item_id > 0 ? (string) get_the_title( $item_id ) : '',
	'stock'             => ( $item_id > 0 && function_exists( 'gs_get_item_availability_pill_text' ) ) ? gs_get_item_availability_pill_text( $item_id ) : '0/0',
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
	// ACF field mapping: read field values and map them into preview_data.
	$image_field = (array) get_field( 'image', $item_id );
	$image_id    = (int) ( $image_field['ID'] ?? $image_field['id'] ?? 0 );
	$image_url   = isset( $image_field['url'] ) ? (string) $image_field['url'] : '';
	$image_alt   = isset( $image_field['alt'] ) ? trim( (string) $image_field['alt'] ) : '';

	if ( $image_url !== '' ) {
		$preview_data['image']['url'] = $image_url;
	}

	if ( $image_alt !== '' ) {
		$preview_data['image']['alt'] = $image_alt;
	}

	if ( $image_id > 0 ) {
		$resolved_image_url = (string) wp_get_attachment_image_url( $image_id, 'gs-item-detail' );
		if ( $resolved_image_url === '' ) {
			$resolved_image_url = (string) wp_get_attachment_image_url( $image_id, 'large' );
		}

		$preview_data['image']['url']    = $resolved_image_url;
		$preview_data['image']['srcset'] = (string) wp_get_attachment_image_srcset( $image_id, 'gs-item-detail' );

		if ( $preview_data['image']['alt'] === '' ) {
			$image_alt_meta = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( is_string( $image_alt_meta ) ) {
				$preview_data['image']['alt'] = trim( $image_alt_meta );
			}
		}
	}

	if ( $preview_data['image']['alt'] === '' ) {
		$preview_data['image']['alt'] = $preview_data['name'] !== '' ? $preview_data['name'] : 'Item image';
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

	$tag_ids = array();
	foreach ( (array) get_field( 'tags', $item_id ) as $tag_id ) {
		$tag_id = (int) $tag_id;
		if ( $tag_id > 0 ) {
			$tag_ids[] = $tag_id;
		}
	}

	foreach ( $tag_ids as $tag_id ) {
		$tag_term = get_term( $tag_id, 'item_tag' );
		if ( ! $tag_term || is_wp_error( $tag_term ) ) {
			continue;
		}

		$preview_data['tags'][ $tag_term->term_id ] = array(
			'id'   => (int) $tag_term->term_id,
			'name' => (string) $tag_term->name,
		);
	}

	$storage_ids = array();
	foreach ( (array) get_field( 'storage_location', $item_id ) as $storage_id ) {
		$storage_id = (int) $storage_id;
		if ( $storage_id > 0 ) {
			$storage_ids[] = $storage_id;
		}
	}

	foreach ( $storage_ids as $storage_id ) {
		$storage_term = get_term( $storage_id, 'storage_locations' );
		if ( ! $storage_term || is_wp_error( $storage_term ) ) {
			continue;
		}

		$preview_data['storage_locations'][ $storage_term->term_id ] = array(
			'id'   => (int) $storage_term->term_id,
			'name' => (string) $storage_term->name,
		);
	}

	$preview_data['tags'] = array_values( $preview_data['tags'] );
	$preview_data['storage_locations'] = array_values( $preview_data['storage_locations'] );
}

if ( ! empty( $preview_data['storage_locations'] ) ) {
	$storage_location_names = array();
	foreach ( $preview_data['storage_locations'] as $storage_term ) {
		$storage_name = isset( $storage_term['name'] ) ? trim( (string) $storage_term['name'] ) : '';
		if ( $storage_name === '' ) {
			continue;
		}

		$storage_location_names[] = $storage_name;
	}

	$preview_data['storage_location'] = implode(
		', ',
		$storage_location_names
	);
}

$preview_data = apply_filters( 'gs_item_preview_data', $preview_data, $item_id );

// Render variables: keep template output simple and detached from raw ACF calls.
$item_name = (string) ( $preview_data['name'] ?? '' );
$condition_name = (string) ( $preview_data['condition']['name'] ?? '' );
$tag_terms = is_array( $preview_data['tags'] ?? null ) ? $preview_data['tags'] : array();
$tag_names = array();
foreach ( $tag_terms as $tag ) {
	$tag_name = isset( $tag['name'] ) ? trim( (string) $tag['name'] ) : '';
	if ( $tag_name !== '' ) {
		$tag_names[] = $tag_name;
	}
}
$storage_location = (string) ( $preview_data['storage_location'] ?? '' );
$image_url = (string) ( $preview_data['image']['url'] ?? '' );
$image_alt = (string) ( $preview_data['image']['alt'] ?? '' );
$image_srcset = (string) ( $preview_data['image']['srcset'] ?? '' );
$image_sizes = (string) ( $preview_data['image']['sizes'] ?? '(max-width: 768px) 100vw, 24rem' );

if ( '' === $image_alt ) {
	$image_alt = $item_name !== '' ? $item_name : 'Item image';
}

$storage_terms = is_array( $preview_data['storage_locations'] ?? null ) ? $preview_data['storage_locations'] : array();
if ( '' === $storage_location && ! empty( $storage_terms ) ) {
	$storage_names = array();
	foreach ( $storage_terms as $storage_term ) {
		$storage_name = isset( $storage_term['name'] ) ? trim( (string) $storage_term['name'] ) : '';
		if ( $storage_name !== '' ) {
			$storage_names[] = $storage_name;
		}
	}

	$storage_location = implode(
		', ',
		$storage_names
	);
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

	<?php if ( $show_data_table || ( $show_taxonomies && ( '' !== $condition_name || ! empty( $tag_names ) || '' !== $storage_location ) ) || ( $show_image && $has_image ) ) : ?>
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
					<?php echo ( $is_modal && '' === $condition_name ) ? 'hidden' : ''; ?>
				>
					<span class="item-preview-data-key">Condition:</span>
					<span
						class="item-preview-tag item-preview-condition-pill"
						<?php echo $is_modal ? 'id="item-modal-condition" data-item-field="condition"' : ''; ?>
					>
						<?php echo esc_html( $condition_name ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $show_taxonomies && ( '' !== $storage_location || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row"
					<?php echo $is_modal ? 'data-item-field-row="storage_location"' : ''; ?>
					<?php echo ( $is_modal && '' === $storage_location ) ? 'hidden' : ''; ?>
				>
					<span class="item-preview-data-key">Storage location:</span>
					<span
						class="item-preview-tag item-preview-storage-pill"
						<?php echo $is_modal ? 'id="item-modal-storage-location" data-item-field="storage_location"' : ''; ?>
					>
						<?php echo esc_html( $storage_location ); ?>
					</span>
				</div>
			<?php endif; ?>

			<?php if ( $show_taxonomies && ( ! empty( $tag_names ) || $is_modal ) ) : ?>
				<div
					class="item-preview-data-row"
					<?php echo $is_modal ? 'data-item-field-row="tags"' : ''; ?>
					<?php echo ( $is_modal && empty( $tag_names ) ) ? 'hidden' : ''; ?>
				>
					<span class="item-preview-data-key">Tags:</span>
					<div
						class="item-preview-tag-list"
						aria-label="Item tags"
						<?php echo $is_modal ? 'id="item-modal-tags" data-item-field="tags"' : ''; ?>
					>
						<?php foreach ( $tag_names as $tag_name ) : ?>
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