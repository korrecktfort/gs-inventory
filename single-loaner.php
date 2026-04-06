<?php
if (!is_user_logged_in()) {
	get_header();
	get_template_part('template-parts/ui/login-mask', null, [
		'title' => get_the_title() ?: 'Loaner',
	]);
	get_footer();
	return;
}

get_header();
?>

<main class="loaner-single-page">
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : the_post(); ?>
			<?php
			$loaner_id = get_the_ID();
			$loaner_info = trim((string) get_field('info', $loaner_id));
			$loan_history = get_posts([
				'post_type' => 'loan',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'orderby' => 'date',
				'order' => 'DESC',
				'meta_query' => [
					[
						'key' => 'related_loaner',
						'value' => $loaner_id,
						'compare' => '=',
					],
				],
			]);

			usort($loan_history, static function ($left, $right) {
				$left_status = (string) get_field('status', $left->ID);
				$right_status = (string) get_field('status', $right->ID);

				$left_returned = ($left_status === 'returned');
				$right_returned = ($right_status === 'returned');

				if ($left_returned !== $right_returned) {
					return $left_returned ? 1 : -1;
				}

				return strcmp((string) $right->post_date, (string) $left->post_date);
			});

			$format_date_for_row = static function ($date_value) {
				if (empty($date_value)) {
					return '—';
				}

				$raw = trim((string) $date_value);
				$timestamp = false;

				foreach (['Y-m-d', 'Ymd', 'd/m/Y', 'm/d/Y', 'd.m.Y'] as $format) {
					$dt = DateTime::createFromFormat($format, $raw);

					if ($dt instanceof DateTime) {
						$timestamp = $dt->getTimestamp();
						break;
					}
				}

				if ($timestamp === false) {
					$parsed = strtotime($raw);

					if ($parsed !== false) {
						$timestamp = $parsed;
					}
				}

				if ($timestamp === false) {
					return $raw;
				}

				return sprintf('%s (%s)', wp_date('d.m.Y', $timestamp), wp_date('l', $timestamp));
			};
			?>
			<article class="item-modal-dialog item-single-dialog loaner-single-card">
				<header class="item-modal-head loaner-single-head">
					<h1 class="item-modal-title loaner-single-title"><?php the_title(); ?></h1>
				</header>

				<div class="item-modal-content loaner-single-content">
					<div class="item-preview item-preview--modal loaner-preview">
						<div class="item-preview-data">
							<div class="item-preview-data-row">
								<span class="item-preview-data-key">Info</span>
								<div class="loaner-single-info-wrap">
					<?php if ($loaner_info !== '') : ?>
						<p class="loaner-single-info"><?php echo nl2br(esc_html($loaner_info)); ?></p>
					<?php else : ?>
						<p class="loaner-single-info loaner-single-info--empty">No additional info available.</p>
					<?php endif; ?>
								</div>
							</div>
						</div>
					</div>

					<section class="loaner-history">
						<span class="loan-single-items-heading loaner-history-title">Loan History</span>

						<?php if (!empty($loan_history)) : ?>
							<ul class="loaner-history-list">
								<?php foreach ($loan_history as $loan) : ?>
									<?php
									$status_label = (string) (get_field('status', $loan->ID) ?: 'unknown');
									$status_key = sanitize_html_class(strtolower($status_label));
									$active_date = $status_label === 'active'
										? $format_date_for_row(get_field('due_date', $loan->ID))
										: '—';
									$due_date_label = 'Due Date: ' . $active_date;
									?>
									<li class="loaner-history-row">
										<a class="loaner-history-link gs-arrow-link" href="<?php echo esc_url(get_permalink($loan->ID)); ?>">
											<?php echo esc_html(get_the_title($loan->ID)); ?>
										</a>
										<span class="loaner-history-date"><?php echo esc_html($due_date_label); ?></span>
										<span class="loan-status-pill loan-status-pill--<?php echo esc_attr($status_key); ?>">
											<?php echo esc_html($status_label); ?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="loaner-history-empty">No loans found for this loaner.</p>
						<?php endif; ?>
					</section>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p>No loaner found.</p>
	<?php endif; ?>
</main>

<?php
get_footer();
?>

