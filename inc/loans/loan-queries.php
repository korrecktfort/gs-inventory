<?php

if (!function_exists('gs_get_loaned_quantities_map')) {
    function gs_get_loaned_quantities_map(): array
    {
        $loanedQuantities = [];

        $activeLoans = get_posts([
            'post_type'      => 'loan',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'status',
                    'value'   => 'active',
                    'compare' => '=',
                ],
            ],
        ]);

        if (empty($activeLoans)) {
            return $loanedQuantities;
        }

        $loanItems = get_posts([
            'post_type'      => 'loan_item',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'related_loan',
                    'value'   => $activeLoans,
                    'compare' => 'IN',
                ],
            ],
        ]);

        if (empty($loanItems)) {
            return $loanedQuantities;
        }

        foreach ($loanItems as $loanItemId) {
            $itemId = (int) get_field('related_item', $loanItemId);
            $quantity = (int) get_field('quantity', $loanItemId);

            if ($itemId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($loanedQuantities[$itemId])) {
                $loanedQuantities[$itemId] = 0;
            }

            $loanedQuantities[$itemId] += $quantity;
        }

        return $loanedQuantities;
    }
}