<?php

if (!function_exists('gs_handle_return_loan')) {
    function gs_handle_return_loan(): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        if (empty($_POST['return_loan_submit'])) {
            return [];
        }

        if (
            empty($_POST['return_loan_nonce']) ||
            !wp_verify_nonce($_POST['return_loan_nonce'], 'return_loan_action')
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