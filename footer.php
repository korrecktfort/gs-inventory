<?php
$admin_url = admin_url();
$logout_url = wp_logout_url(home_url('/'));
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$display_name = $is_logged_in
    ? ($current_user->display_name ?: $current_user->user_login)
    : '';
?>

<footer class="gs-site-footer" role="contentinfo">
	<div class="gs-site-footer-bar">
		<div class="gs-site-footer-left">
			<a class="ui-button gs-footer-link" href="<?php echo esc_url($admin_url); ?>">wp-admin</a>
		</div>

		<?php if ($is_logged_in) : ?>
			<p class="gs-footer-user-note">Logged in as <strong><?php echo esc_html($display_name); ?></strong></p>
		<?php endif; ?>

		<div class="gs-site-footer-right">
			<?php if ($is_logged_in) : ?>
				<a class="ui-button gs-footer-link" href="<?php echo esc_url($logout_url); ?>">logout</a>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>