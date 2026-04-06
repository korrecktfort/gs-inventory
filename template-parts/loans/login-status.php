<div>
    <?php if (is_user_logged_in()): ?>
        <p>Welcome, <?php echo esc_html(wp_get_current_user()->user_login); ?>!</p>
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="button ui-button">Logout</a>
    <?php endif; ?>
</div>