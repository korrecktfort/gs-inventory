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

        $nonce = isset($_POST['create_loan_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['create_loan_nonce']))
            : '';

        if ($nonce === '' || !wp_verify_nonce($nonce, 'create_loan_action')) {
            return [
                'success' => false,
                'message' => 'Security check failed.',
            ];
        }

        if (!is_user_logged_in() || !current_user_can('read')) {
            return [
                'success' => false,
                'message' => 'You need to be logged in to create a loan.',
            ];
        }

        if (!function_exists('gs_get_loaned_quantities_map')) {
            require_once get_template_directory() . '/inc/loans/loan-queries.php';
        }

        $loan_title  = sanitize_text_field(wp_unslash((string) ($_POST['loan_title'] ?? '')));
        $loaner_id = isset($_POST['loaner_id']) ? (int) $_POST['loaner_id'] : 0;
        $loan_status = 'active';
        $loan_start_date = sanitize_text_field(wp_unslash((string) ($_POST['start_date'] ?? '')));
        $loan_due_date = sanitize_text_field(wp_unslash((string) ($_POST['due_date'] ?? '')));
        $current_user_id = get_current_user_id();

        $loan_items  = isset($_POST['loan_items']) && is_array($_POST['loan_items']) ? $_POST['loan_items'] : [];

        if ($current_user_id <= 0) {
            return [
                'success' => false,
                'message' => 'You need to be logged in to create a loan.',
            ];
        }

        if ($loan_title === '') {
            return [
                'success' => false,
                'message' => 'Loan title is required.',
            ];
        }

        if ($loaner_id <= 0) {
            return [
                'success' => false,
                'message' => 'Please select a valid loaner.',
            ];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loan_start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $loan_due_date)) {
            return [
                'success' => false,
                'message' => 'Please provide valid start and due dates.',
            ];
        }

        $start_date_obj = DateTimeImmutable::createFromFormat('Y-m-d', $loan_start_date);
        $due_date_obj = DateTimeImmutable::createFromFormat('Y-m-d', $loan_due_date);

        if (!$start_date_obj || !$due_date_obj) {
            return [
                'success' => false,
                'message' => 'Please provide valid start and due dates.',
            ];
        }

        if ($due_date_obj < $start_date_obj) {
            return [
                'success' => false,
                'message' => 'Due date cannot be earlier than start date.',
            ];
        }

        $loaner_post = get_post($loaner_id);
        if (!$loaner_post || $loaner_post->post_type !== 'loaner' || $loaner_post->post_status !== 'publish') {
            return [
                'success' => false,
                'message' => 'Please select a valid loaner.',
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

            $item_post = get_post($item_id);
            if (!$item_post || $item_post->post_type !== 'item' || $item_post->post_status !== 'publish') {
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
            'post_author' => $current_user_id,
        ]);

        if (is_wp_error($loan_id) || !$loan_id) {
            return [
                'success' => false,
                'message' => 'Loan could not be created.',
            ];
        }

        update_field('status', $loan_status, $loan_id);
        update_field('related_loaner', $loaner_id, $loan_id);
        update_field('start_date', $loan_start_date, $loan_id);
        update_field('due_date', $loan_due_date, $loan_id);
        update_field('user', $current_user_id, $loan_id);

        foreach ($validated_items as $validated_item) {
            $item_id  = $validated_item['item_id'];
            $quantity = $validated_item['quantity'];

            $loan_item_id = wp_insert_post([
                'post_type'   => 'loan_item',
                'post_status' => 'publish',
                'post_title'  => get_the_title($item_id) . ' x ' . $quantity,
                'post_author' => $current_user_id,
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