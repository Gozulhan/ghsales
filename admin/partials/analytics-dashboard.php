<?php
/**
 * Analytics Dashboard Template
 *
 * HTML template for the GHSales Analytics Dashboard admin page.
 * Displays tracking data, product stats, and performance metrics.
 *
 * @package GHSales
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap ghsales-analytics-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Analytics Dashboard', 'ghsales' ); ?>
		<span class="ghsales-version">v<?php echo esc_html( GHSALES_VERSION ); ?></span>
	</h1>

	<p class="ghsales-subtitle">
		<?php esc_html_e( 'Track user behavior, product performance, and sales metrics', 'ghsales' ); ?>
	</p>

	<hr class="wp-header-end">

	<!-- Overview Cards -->
	<div class="ghsales-overview-cards">
		<div class="ghsales-stat-card ghsales-stat-primary">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-visibility"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo number_format( $overview_stats['views'] ); ?></h3>
				<p><?php esc_html_e( 'Total Views', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'Last 7 days', 'ghsales' ); ?></span>
			</div>
		</div>

		<div class="ghsales-stat-card ghsales-stat-success">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-cart"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo number_format( $overview_stats['add_to_carts'] ); ?></h3>
				<p><?php esc_html_e( 'Add to Carts', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'Last 7 days', 'ghsales' ); ?></span>
			</div>
		</div>

		<div class="ghsales-stat-card ghsales-stat-info">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-yes-alt"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo number_format( $overview_stats['purchases'] ); ?></h3>
				<p><?php esc_html_e( 'Purchases', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'Last 7 days', 'ghsales' ); ?></span>
			</div>
		</div>

		<div class="ghsales-stat-card ghsales-stat-warning">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-chart-line"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo esc_html( $overview_stats['conversion_rate'] ); ?>%</h3>
				<p><?php esc_html_e( 'Conversion Rate', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'Last 7 days', 'ghsales' ); ?></span>
			</div>
		</div>

		<div class="ghsales-stat-card ghsales-stat-revenue">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-money-alt"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo wc_price( $overview_stats['revenue'] ); ?></h3>
				<p><?php esc_html_e( 'Total Revenue', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'All time', 'ghsales' ); ?></span>
			</div>
		</div>

		<div class="ghsales-stat-card ghsales-stat-secondary">
			<div class="ghsales-stat-icon">
				<span class="dashicons dashicons-products"></span>
			</div>
			<div class="ghsales-stat-content">
				<h3><?php echo number_format( $overview_stats['products_tracked'] ); ?></h3>
				<p><?php esc_html_e( 'Products Tracked', 'ghsales' ); ?></p>
				<span class="ghsales-stat-period"><?php esc_html_e( 'Database', 'ghsales' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Main Content Grid -->
	<div class="ghsales-dashboard-grid">

		<!-- Left Column - Top Products -->
		<div class="ghsales-dashboard-main">

			<!-- Top Products Section -->
			<div class="ghsales-dashboard-section">
				<h2><?php esc_html_e( 'Top Products', 'ghsales' ); ?></h2>

				<!-- Tab Navigation -->
				<div class="ghsales-tabs">
					<button class="ghsales-tab active" data-tab="views"><?php esc_html_e( 'Most Viewed', 'ghsales' ); ?></button>
					<button class="ghsales-tab" data-tab="conversions"><?php esc_html_e( 'Top Converting', 'ghsales' ); ?></button>
					<button class="ghsales-tab" data-tab="revenue"><?php esc_html_e( 'Revenue Leaders', 'ghsales' ); ?></button>
					<button class="ghsales-tab" data-tab="trending"><?php esc_html_e( 'Trending', 'ghsales' ); ?></button>
					<button class="ghsales-tab" data-tab="low"><?php esc_html_e( 'Low Performing', 'ghsales' ); ?></button>
				</div>

				<!-- Tab Content - Most Viewed -->
				<div class="ghsales-tab-content active" id="tab-views">
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Views', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Price', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $top_viewed ) ) : ?>
								<?php foreach ( $top_viewed as $product ) : ?>
									<tr>
										<td>
											<div class="ghsales-product-cell">
												<?php echo $product['thumbnail']; ?>
												<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
													<?php echo esc_html( $product['name'] ); ?>
												</a>
											</div>
										</td>
										<td><strong><?php echo number_format( $product['metric_value'] ); ?></strong></td>
										<td><?php echo $product['price']; ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3"><?php esc_html_e( 'No product view data yet.', 'ghsales' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Tab Content - Top Converting -->
				<div class="ghsales-tab-content" id="tab-conversions">
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Conversion Rate', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Conversions', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $top_converting ) ) : ?>
								<?php foreach ( $top_converting as $product ) : ?>
									<tr>
										<td>
											<div class="ghsales-product-cell">
												<?php echo $product['thumbnail']; ?>
												<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
													<?php echo esc_html( $product['name'] ); ?>
												</a>
											</div>
										</td>
										<td><strong><?php echo esc_html( $product['conversion_rate'] ); ?>%</strong></td>
										<td><?php echo number_format( $product['conversions'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3"><?php esc_html_e( 'No conversion data yet.', 'ghsales' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Tab Content - Revenue Leaders -->
				<div class="ghsales-tab-content" id="tab-revenue">
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Revenue', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Price', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $revenue_leaders ) ) : ?>
								<?php foreach ( $revenue_leaders as $product ) : ?>
									<tr>
										<td>
											<div class="ghsales-product-cell">
												<?php echo $product['thumbnail']; ?>
												<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
													<?php echo esc_html( $product['name'] ); ?>
												</a>
											</div>
										</td>
										<td><strong><?php echo wc_price( $product['metric_value'] ); ?></strong></td>
										<td><?php echo $product['price']; ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3"><?php esc_html_e( 'No revenue data yet.', 'ghsales' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Tab Content - Trending -->
				<div class="ghsales-tab-content" id="tab-trending">
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Trend Score', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Recent Views', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $trending ) ) : ?>
								<?php foreach ( $trending as $product ) : ?>
									<tr>
										<td>
											<div class="ghsales-product-cell">
												<?php echo $product['thumbnail']; ?>
												<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
													<?php echo esc_html( $product['name'] ); ?>
												</a>
											</div>
										</td>
										<td><strong><?php echo esc_html( $product['trend_score'] ); ?>%</strong></td>
										<td><?php echo number_format( $product['views'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3"><?php esc_html_e( 'No trending data yet.', 'ghsales' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<!-- Tab Content - Low Performing -->
				<div class="ghsales-tab-content" id="tab-low">
					<p class="ghsales-tab-description">
						<?php esc_html_e( 'Products with high views but low conversions - good candidates for discounts', 'ghsales' ); ?>
					</p>
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Views', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Conversion Rate', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $low_performing ) ) : ?>
								<?php foreach ( $low_performing as $product ) : ?>
									<tr>
										<td>
											<div class="ghsales-product-cell">
												<?php echo $product['thumbnail']; ?>
												<a href="<?php echo esc_url( $product['permalink'] ); ?>" target="_blank">
													<?php echo esc_html( $product['name'] ); ?>
												</a>
											</div>
										</td>
										<td><?php echo number_format( $product['views'] ); ?></td>
										<td><strong><?php echo esc_html( $product['conversion_rate'] ); ?>%</strong></td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="3"><?php esc_html_e( 'No low-performing products found.', 'ghsales' ); ?></td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Search Analytics Section -->
			<div class="ghsales-dashboard-section">
				<h2><?php esc_html_e( 'Search Analytics', 'ghsales' ); ?></h2>
				<table class="ghsales-analytics-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Search Query', 'ghsales' ); ?></th>
							<th><?php esc_html_e( 'Count', 'ghsales' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $search_analytics ) ) : ?>
							<?php foreach ( $search_analytics as $search ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $search['query'] ); ?></strong></td>
									<td><?php echo number_format( $search['count'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="2"><?php esc_html_e( 'No search data yet.', 'ghsales' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Active Sales Performance -->
			<?php if ( ! empty( $active_sales ) ) : ?>
				<div class="ghsales-dashboard-section">
					<h2><?php esc_html_e( 'Active Sales Performance', 'ghsales' ); ?></h2>
					<table class="ghsales-analytics-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Sale Event', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Products Viewed', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Products Sold', 'ghsales' ); ?></th>
								<th><?php esc_html_e( 'Total Purchases', 'ghsales' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $active_sales as $sale ) : ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $sale['name'] ); ?></strong>
										<br>
										<small><?php echo esc_html( date( 'M j, Y', strtotime( $sale['start_date'] ) ) ); ?> - <?php echo esc_html( date( 'M j, Y', strtotime( $sale['end_date'] ) ) ); ?></small>
									</td>
									<td><?php echo number_format( $sale['products_viewed'] ); ?></td>
									<td><?php echo number_format( $sale['products_sold'] ); ?></td>
									<td><strong><?php echo number_format( $sale['total_purchases'] ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

		</div>

		<!-- Right Column - Activity Feed & Breakdown -->
		<div class="ghsales-dashboard-sidebar">

			<!-- Activity Breakdown -->
			<div class="ghsales-dashboard-section">
				<h2><?php esc_html_e( 'Activity Breakdown', 'ghsales' ); ?></h2>
				<div class="ghsales-activity-breakdown">
					<?php foreach ( $activity_counts as $type => $count ) : ?>
						<div class="ghsales-activity-item">
							<span class="ghsales-activity-label"><?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?>:</span>
							<span class="ghsales-activity-count"><?php echo number_format( $count ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Recent Activity Feed -->
			<div class="ghsales-dashboard-section">
				<h2>
					<?php esc_html_e( 'Recent Activity', 'ghsales' ); ?>
					<span class="ghsales-live-indicator"></span>
				</h2>
				<div class="ghsales-activity-feed" id="ghsales-activity-feed">
					<?php if ( ! empty( $recent_activity ) ) : ?>
						<?php foreach ( $recent_activity as $activity ) : ?>
							<div class="ghsales-activity-entry">
								<span class="ghsales-activity-icon dashicons dashicons-<?php echo esc_attr( GHSales_Analytics_Page::get_activity_icon( $activity['type'] ) ); ?>"></span>
								<div class="ghsales-activity-content">
									<p><?php echo esc_html( $activity['message'] ); ?></p>
									<span class="ghsales-activity-time"><?php echo esc_html( $activity['time_ago'] ); ?> <?php esc_html_e( 'ago', 'ghsales' ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ghsales-no-activity"><?php esc_html_e( 'No recent activity.', 'ghsales' ); ?></p>
					<?php endif; ?>
				</div>
			</div>

		</div>

	</div>
</div>
