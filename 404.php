<?php get_header(); ?>

<main class="gs-page">
	<section class="border" style="padding: 1rem; margin: 1rem 0;">
		<h1>Page not found</h1>
		<p>The page you requested does not exist or may have moved.</p>
		<p><a class="ui-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Go to homepage</a></p>
	</section>
</main>

<?php get_footer(); ?>