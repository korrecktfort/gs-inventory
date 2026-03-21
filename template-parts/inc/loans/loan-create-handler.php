<?php

function gs_handle_create_loan(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {        
        return [];
    }

    if (empty($_POST['create_loan_submit'])) {        
        return [];
    }

    if (
        empty($_POST['create_loan_nonce']) ||
        !wp_verify_nonce($_POST['create_loan_nonce'], 'create_loan_action')
    ) {        
        return [
            'success' => false,
            'message' => 'Nonce validation failed.',
        ];
    }

    $loan_title  = sanitize_text_field($_POST['loan_title'] ?? '');
    $loan_status = sanitize_text_field($_POST['loan_status'] ?? 'active');
    $loan_items  = $_POST['loan_items'] ?? [];

    if ($loan_title === '') {
        return [
            'success' => false,
            'message' => 'Loan title is required.',
        ];
    }

    $loan_id = wp_insert_post([
        'post_type'   => 'loan',
        'post_status' => 'publish',
        'post_title'  => $loan_title,
    ]);

    if (is_wp_error($loan_id) || !$loan_id) {
        return [
            'success' => false,
            'message' => 'Loan could not be created.',
        ];
    }

    update_field('status', $loan_status, $loan_id);

    foreach ($loan_items as $row) {
        $item_id   = isset($row['item']) ? (int)$row['item'] : 0;
        $quantity  = isset($row['quantity']) ? (int)$row['quantity'] : 0;

        if ($item_id <= 0 || $quantity <= 0) {
            continue;
        }

        $loan_item_id = wp_insert_post([
            'post_type'   => 'loan_item',
            'post_status' => 'publish',
            'post_title'  => get_the_title($item_id) . ' x ' . $quantity,
        ]);

        if (is_wp_error($loan_item_id) || !$loan_item_id) {
            continue;
        }

        update_field('related_item', $item_id, $loan_item_id);
        update_field('related_loan', $loan_id, $loan_item_id);
        update_field('quantity', $quantity, $loan_item_id);
    }

    return [
        'success' => true,
        'message' => 'Loan created successfully.',
        'loan_id'  => $loan_id,
    ];
}