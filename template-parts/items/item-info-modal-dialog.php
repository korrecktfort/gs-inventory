<?php
$item_id = isset( $args['item_id'] ) ? (int) $args['item_id'] : 0;
$item_name = $item_id > 0 ? (string) get_the_title( $item_id ) : '';
?>

<div class="item-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
	<div class="item-modal-head">
		<h2 id="item-modal-title" class="item-modal-title"><?php echo esc_html( $item_name ); ?></h2>
		<button type="button" class="item-modal-close ui-button" aria-label="Close modal">×</button>
	</div>

	<div class="item-modal-content">
		<?php get_template_part(
			'template-parts/items/item',
			'preview',
			array(
				'item_id' => $item_id,
				'mode' => 'modal',
				'show_title' => false,
				'show_taxonomies' => true,
				'show_data_table' => true,
				'root_class' => 'item-preview--modal-card',
			)
		); ?>
	</div>
</div>
