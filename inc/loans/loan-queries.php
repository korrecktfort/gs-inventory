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

if (!function_exists('gs_get_loan_items_for_loan')) {
    function gs_get_loan_items_for_loan(int $loan_id): array
    {
        if ($loan_id <= 0) {
            return [];
        }

        return get_posts([
            'post_type'      => 'loan_item',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => 'related_loan',
                    'value'   => $loan_id,
                    'compare' => '=',
                ],
            ],
        ]);
    }
}

if (!function_exists('gs_get_loan_summary')) {
    function gs_get_loan_summary(int $loan_id): array
    {
        $loanItems = gs_get_loan_items_for_loan($loan_id);

        $positionCount = 0;
        $totalQuantity = 0;

        foreach ($loanItems as $loanItem) {
            $quantity = (int) get_field('quantity', $loanItem->ID);

            if ($quantity <= 0) {
                continue;
            }

            $positionCount++;
            $totalQuantity += $quantity;
        }

        return [
            'position_count' => $positionCount,
            'total_quantity' => $totalQuantity,
        ];
    }
}