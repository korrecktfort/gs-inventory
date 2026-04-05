<?php
$loaner = $args['loaner'] ?? null;

if (!$loaner) {
    return;
}

$loaner_id = (int) $loaner->ID;
$permalink = get_permalink($loaner_id);
?>

<article class="loaner-overview-row">
    <div class="loaner-overview-card">
        <h2 class="loaner-overview-title">
            <a class="gs-arrow-link" href="<?php echo esc_url($permalink); ?>">
                <?php echo esc_html(get_the_title($loaner_id)); ?>
            </a>
        </h2>
    </div>
</article>
