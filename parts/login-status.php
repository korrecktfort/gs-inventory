<div>
    <?php if (is_user_logged_in()): ?>
        <p>Welcome, <?php echo wp_get_current_user()->user_login; ?>!</p>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="button">Logout</a>
    <?php endif; ?>
</div>