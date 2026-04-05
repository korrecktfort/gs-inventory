<div id="item-modal-trigger" class="item-modal-trigger item-modal-style" hidden>
    <div class="item-modal-backdrop"></div>

    <div class="item-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
        <div class="item-modal-head">
            <h2 id="item-modal-title" class="item-modal-title"></h2>
            <button type="button" class="item-modal-close ui-button" aria-label="Close modal">×</button>
        </div>

        <div class="item-modal-content">
            <?php get_template_part('template-parts/items/item', 'preview', [
                'mode' => 'modal',
                'show_title' => false,
                'show_taxonomies' => true,
                'show_data_table' => true,
                'root_class' => 'item-preview--modal-card',
            ]); ?>
        </div>
    </div>
</div>

