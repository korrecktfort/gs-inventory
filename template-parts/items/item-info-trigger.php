<?php
$item_id = $args['item_id'] ?? 0;

if ( ! $item_id ) {
	return;
}

$item_name = (string) get_the_title( $item_id );
?>

<button
	type="button"
	class="open-item-modal item-info-trigger ui-button"
	data-item-id="<?php echo esc_attr( $item_id ); ?>"
>
	<span class="item-info-trigger-label"><?php echo esc_html( $item_name ); ?></span>
</button>
