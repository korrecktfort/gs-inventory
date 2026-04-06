<?php

if (!function_exists('gs_handle_return_loan')) {
    function gs_handle_return_loan(): array
    {
        $request_method = isset($_SERVER['REQUEST_METHOD'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_METHOD']))
            : 'GET';

        if (strtoupper($request_method) !== 'POST') {
            return [];
        }

        if (empty($_POST['return_loan_submit'])) {
            return [];
        }

        if (!function_exists('gs_can_manage_inventory') || !gs_can_manage_inventory()) {
            return [
                'success' => false,
                'message' => 'You need to be logged in to return a loan.',
            ];
        }

        $nonce = isset($_POST['return_loan_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['return_loan_nonce']))
            : '';

        if (
            $nonce === '' ||
            !wp_verify_nonce($nonce, 'return_loan_action')
        ) {
            return [
                'success' => false,
                'message' => 'Security check failed.',
            ];
        }

        $loan_id = isset($_POST['loan_id']) ? (int) $_POST['loan_id'] : 0;

        if ($loan_id <= 0 || get_post_type($loan_id) !== 'loan') {
            return [
                'success' => false,
                'message' => 'Invalid loan.',
            ];
        }

        $current_status = get_field('status', $loan_id);

        if ($current_status !== 'active') {
            return [
                'success' => false,
                'message' => 'Loan is not active.',
            ];
        }

        update_field('status', 'returned', $loan_id);

        return [
            'success' => true,
            'loan_id'  => $loan_id,
        ];
    }
}

?>