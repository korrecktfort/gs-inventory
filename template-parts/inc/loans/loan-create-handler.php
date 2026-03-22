<?php

if (!function_exists('gs_handle_create_loan')) {
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
                'message' => 'Security check failed.',
            ];
        }

        if (!function_exists('gs_get_loaned_quantities_map')) {
            require_once get_template_directory() . '/inc/loans/loan-queries.php';
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

        $loaned_quantities = gs_get_loaned_quantities_map();

        $validated_items = [];

        foreach ($loan_items as $item_id => $row) {
            $item_id  = (int) $item_id;
            $quantity = isset($row['quantity']) ? (int) $row['quantity'] : 0;

            if ($item_id <= 0 || $quantity <= 0) {
                continue;
            }

            $stock_total = (int) get_field('stock_total', $item_id);
            $loaned      = (int) ($loaned_quantities[$item_id] ?? 0);
            $available   = max(0, $stock_total - $loaned);

            if ($quantity > $available) {
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Not enough stock for "%s". Available: %d, requested: %d.',
                        get_the_title($item_id),
                        $available,
                        $quantity
                    ),
                ];
            }

            $validated_items[] = [
                'item_id'  => $item_id,
                'quantity' => $quantity,
            ];
        }

        if (empty($validated_items)) {
            return [
                'success' => false,
                'message' => 'Please select at least one item.',
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

        foreach ($validated_items as $validated_item) {
            $item_id  = $validated_item['item_id'];
            $quantity = $validated_item['quantity'];

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
            'loan_id' => $loan_id,
        ];
    }
}