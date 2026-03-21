<?php 
extract($args);
setup_postdata($post);

$id = $post->ID;

$image = get_field('image', $id);
$storage_location = get_field('storage_location', $id);
$description = get_field('description', $id);
$condition = get_field('condition', $id);
$total_quantity = get_field('total_quantity', $id);
$additional_notes = get_field('additional_notes', $id);
?>

<div class="item-card">    
    <div class="item-image">
        <?php if ($image): ?>
            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" style="max-width: 100px; height: auto;">
        <?php else: ?>
            <img src="<?php echo get_template_directory_uri(); ?>/images/no-image.png" alt="No Image" style="max-width: 100px; height: auto;">
        <?php endif; ?>
    </div>

    <div class="item-details">
        <h2><?php the_title(); ?></h2>
        <p><strong>Storage Location:</strong> <?php echo esc_html($storage_location ?: '—'); ?></p>
        <p><strong>Description:</strong> <?php echo esc_html($description ?: '—'); ?></p>
        <p><strong>Condition:</strong> <?php echo esc_html($condition ?: '—'); ?></p>
        <p><strong>Total Quantity:</strong> <?php echo esc_html($total_quantity ?: '—'); ?></p>
        <p><strong>Additional Notes:</strong> <?php echo esc_html($additional_notes ?: '—'); ?></p>
    </div>    
</div>