<?php
$title = trim((string) ($args['title'] ?? '')); 
$redirect_target = home_url(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
?>

<main class="gs-login-mask-page">
    <section class="gs-login-mask border">
        <h1 class="gs-login-mask-title">
            <?php if ($title !== '') : ?>
                <?php echo esc_html($title); ?>
            <?php else : ?>
                Please sign in
            <?php endif; ?>
        </h1>
        <p class="gs-login-mask-text">You need to be logged in to access this page.</p>
        <?php
        wp_login_form([
            'redirect' => $redirect_target,
            'label_username' => __('Username'),
            'label_password' => __('Password'),
            'label_remember' => __('Remember Me'),
            'label_log_in' => __('Log In'),
            'remember' => true,
        ]);
        ?>
    </section>
</main>