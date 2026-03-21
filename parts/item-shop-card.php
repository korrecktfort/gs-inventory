<?php 
extract($args);
setup_postdata($post);

$id = $post->ID;
?>

<div class="item-shop-card">
    <?php get_template_part('parts/item-card', null, array('id' => $id)); ?>
    <div class="item-shop-actions">
        <button class="decrease-btn" data-id="<?php echo esc_attr($id); ?>">-</button>
        <span class="item-count" data-id="<?php echo esc_attr($id); ?>">0</span>
        <button class="increase-btn" data-id="<?php echo esc_attr($id); ?>">+</button>
    </div>
</div>