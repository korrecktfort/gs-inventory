<div class="loan-title-field">
    <label for="loan_title" class="ui-label">Loan Name</label>
    <input class="text-field ui-input" type="text" name="loan_title" id="loan_title" required>
</div>


<section class="loaner-main">
    <?php 
$loaners = get_posts([
    'post_type'      => 'loaner',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    ]);
    ?>

<label for="loaner-search" class="loaner-label ui-label">Loaner</label>
<div
    class="loaner-picker"
    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-update-nonce="<?php echo esc_attr(wp_create_nonce('gs_update_loaner_info')); ?>"
    data-create-nonce="<?php echo esc_attr(wp_create_nonce('gs_create_loaner')); ?>"
>

    <div class="loaner-body">
    <div class="loaner-control">
        <input
            type="text"
            id="loaner-search"
            class="loaner-search ui-input"
            placeholder="Select loaner..."
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="loaner-list"
        >
        <button type="button" class="loaner-toggle ui-button" aria-label="Toggle loaner list">▾</button>

        <div class="loaner-list" id="loaner-list" hidden>
            <?php foreach ($loaners as $loaner) : ?>
                <?php
                $loaner_id = $loaner->ID;
                $loaner_title = get_the_title($loaner_id);
                $loaner_info = get_field('info', $loaner_id);
                ?>
                <button
                    type="button"
                    class="loaner-option"
                    data-loaner-id="<?php echo esc_attr($loaner_id); ?>"
                    data-loaner-info="<?php echo esc_attr((string) $loaner_info); ?>"
                >
                    <?php echo esc_html($loaner_title); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <input type="hidden" name="loaner_id" id="loaner-id" value="">

    <div class="loaner-info" id="loaner-info" aria-live="polite"></div>

    <div class="loaner-info-actions">
        <button type="button" class="loaner-info-edit-btn ui-button" id="loaner-info-edit-btn" disabled>
            Edit info
        </button>
        <button type="button" class="loaner-info-add-btn ui-button" id="loaner-info-add-btn">
            Add loaner
        </button>
    </div>

    <div class="loaner-info-editor" id="loaner-info-editor" hidden>
        <label for="loaner-info-input" class="ui-label">Edit loaner info</label>
        <textarea id="loaner-info-input" class="loaner-info-input ui-input" rows="4"></textarea>
        <div class="loaner-info-editor-actions">
            <button type="button" class="loaner-info-save-btn ui-button" id="loaner-info-save-btn">Save</button>
            <button type="button" class="loaner-info-cancel-btn ui-button" id="loaner-info-cancel-btn">Cancel</button>
        </div>
        <p class="loaner-info-status" id="loaner-info-status" role="status" aria-live="polite"></p>
    </div>

    <div class="loaner-create-editor" id="loaner-create-editor" hidden>
        <label for="loaner-create-name" class="ui-label">New loaner name</label>
        <input type="text" id="loaner-create-name" class="loaner-create-name ui-input" maxlength="120">

        <label for="loaner-create-info" class="ui-label">New loaner info</label>
        <textarea id="loaner-create-info" class="loaner-create-info ui-input" rows="4"></textarea>

        <div class="loaner-info-editor-actions">
            <button type="button" class="loaner-create-save-btn ui-button" id="loaner-create-save-btn">Save new loaner</button>
            <button type="button" class="loaner-create-cancel-btn ui-button" id="loaner-create-cancel-btn">Cancel</button>
        </div>
        <p class="loaner-create-status" id="loaner-create-status" role="status" aria-live="polite"></p>
    </div>
    </div>
</div>
</section>

<div class="loan-meta">   
    <div class="loan-meta-field">
        <label for="start_date" class="ui-label">Start Date</label>
        <input class="date-field ui-input" type="date" name="start_date" id="start_date" required>
    </div>

    <div class ="loan-meta-field">
        <label for="due_date" class="ui-label">Due Date</label>
        <input class="date-field ui-input" type="date" name="due_date" id="due_date" required>
    </div>

</div>

