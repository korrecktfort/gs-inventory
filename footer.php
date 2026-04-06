<?php
$admin_url = admin_url();
$logout_url = wp_logout_url(home_url('/'));
?>

<footer class="gs-site-footer" role="contentinfo">
	<div class="gs-site-footer-bar">
		<a class="ui-button gs-footer-link" href="<?php echo esc_url($admin_url); ?>">wp-admin</a>
		<a class="ui-button gs-footer-link" href="<?php echo esc_url($logout_url); ?>">logout</a>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>