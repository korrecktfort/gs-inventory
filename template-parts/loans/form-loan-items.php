<?php
$items = $args['items'] ?? [];
?>



<div class="loan-items">
    <h2>Items</h2>

    <?php for ($i = 0; $i < 5; $i++) : ?>
        <?php
        get_template_part('template-parts/loans/loan-item', 'row', [
            'index' => $i,
            'items' => $items,
        ]);
        ?>
    <?php endfor; ?>
</div>