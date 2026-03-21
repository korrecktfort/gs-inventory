<?php
// Create Loan Post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_basket'])) {
    $basket_json = stripslashes($_POST['basket_data'] ?? '{}');
    $basket_array = json_decode($basket_json, true);

    if (!empty($basket_array)) {
        $items = [];

        foreach ($basket_array as $item_id => $qty) {
            $item_title = get_the_title($item_id);
            $items[] = [
                'id'    => $item_id,
                'title' => $item_title,
                'qty'   => (int) $qty,
            ];
        }

        $post_id = wp_insert_post([
            'post_type'   => 'loan',
            'post_title'  => 'Loan on ' . current_time('Y-m-d H:i'),
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, 'loan_items', json_encode($items));

            echo '<p>✅ Loan saved! <a href="' . esc_url(get_permalink($post_id)) . '">View it here</a>.</p>';
        } else {
            echo '<p>❌ Error creating loan.</p>';
        }
    } else {
        echo '<p>⚠️ Basket is empty.</p>';
    }
}

// foreach query posts "item" and display them
$args = array(
    'post_type' => 'item',
    'posts_per_page' => -1, // Get all items
);

$query = new WP_Query($args);
if($query->have_posts()) {   
    while($query->have_posts()) 
    {
        $query->the_post();
        $id = get_the_ID();

        get_template_part('parts/item-shop-card', null, array('id' => $id));
    }
    
} else {
    echo '<p>No items found.</p>';
}

wp_reset_postdata();
?>

<form id="save-basket-form" method="post">
    <input type="hidden" name="basket_data" id="basket-data">
    <button type="submit" name="save_basket">Save Basket as Loan</button>
</form>



<script>
document.addEventListener("DOMContentLoaded", function() {
    const storageKey = "basket";
    let basket = JSON.parse(localStorage.getItem(storageKey)) || {};

    function updateDisplay(itemId) {
        const countEl = document.querySelector(`.item-count[data-id="${itemId}"]`);
        countEl.textContent = basket[itemId] || 0;
    }

    function increaseItem(itemId) {
        basket[itemId] = (basket[itemId] || 0) + 1;
        localStorage.setItem(storageKey, JSON.stringify(basket));
        updateDisplay(itemId);
    }

    function decreaseItem(itemId) {
        if (basket[itemId]) {
            basket[itemId] -= 1;
            if (basket[itemId] <= 0) delete basket[itemId];
            localStorage.setItem(storageKey, JSON.stringify(basket));
            updateDisplay(itemId);
        }
    }

    document.querySelectorAll('.increase-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            increaseItem(id);
        });
    });

    document.querySelectorAll('.decrease-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            decreaseItem(id);
        });
    });

    // On load, update all item counts
    document.querySelectorAll('.item-count').forEach(el => {
        const id = el.getAttribute('data-id');
        updateDisplay(id);
    });

    const form = document.getElementById("save-basket-form");
    const hiddenInput = document.getElementById("basket-data");

    form.addEventListener("submit", function(e) {
        const basket = localStorage.getItem("basket") || "{}";
        hiddenInput.value = basket;
    });
});


</script>