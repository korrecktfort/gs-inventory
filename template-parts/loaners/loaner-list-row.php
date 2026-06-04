<?php
$loaner = $args['loaner'] ?? null;

if (!$loaner) {
    return;
}

$loaner_id = (int) $loaner->ID;
$permalink = get_permalink($loaner_id);

$row_data = array(
    'title' => (string) get_the_title($loaner_id),
    'url'   => (string) $permalink,
    'meta'  => array(),
);

$row_data = apply_filters('gs_loaner_list_row_data', $row_data, $loaner);

$title = trim((string) ($row_data['title'] ?? ''));
$url = (string) ($row_data['url'] ?? '');
$meta_rows = is_array($row_data['meta'] ?? null) ? $row_data['meta'] : array();
?>

<article class="loaner-overview-row">
    <div class="loaner-overview-card">
        <h2 class="loaner-overview-title">
            <?php if ($url !== '') : ?>
                <a class="gs-arrow-link" href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($title); ?>
                </a>
            <?php else : ?>
                <?php echo esc_html($title); ?>
            <?php endif; ?>
        </h2>

        <?php if (!empty($meta_rows)) : ?>
            <div class="loaner-overview-meta">
                <?php foreach ($meta_rows as $meta_row) : ?>
                    <?php
                    $meta_text = trim((string) $meta_row);
                    if ($meta_text === '') {
                        continue;
                    }
                    ?>
                    <p class="loaner-overview-meta-item"><?php echo esc_html($meta_text); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php do_action('gs_loaner_list_row_after_title', $loaner, $row_data); ?>
    </div>
</article>
