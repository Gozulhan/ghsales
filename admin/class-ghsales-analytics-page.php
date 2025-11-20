<?php
/**
 * GHSales Analytics Dashboard
 *
 * Admin page to display tracking data, product stats, sales performance,
 * and upsell metrics in a comprehensive dashboard.
 *
 * @package GHSales
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Analytics Dashboard Page Class
 *
 * Handles rendering and data fetching for the analytics admin page.
 *
 * @since 1.1.0
 */
class GHSales_Analytics_Page {

	/**
	 * Initialize analytics page
	 * Registers admin menu and hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_ghsales_get_live_feed', array( __CLASS__, 'ajax_get_live_feed' ) );
	}

	/**
	 * Register Analytics submenu page
	 *
	 * @return void
	 */
	public static function register_menu_page() {
		add_submenu_page(
			'ghsales',              // Parent slug
			__( 'Analytics', 'ghsales' ),
			__( 'Analytics', 'ghsales' ),
			'manage_woocommerce',   // Capability
			'ghsales-analytics',    // Menu slug
			array( __CLASS__, 'render_analytics_page' )
		);
	}

	/**
	 * Enqueue CSS and JS assets for analytics page
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		// Only load on our analytics page
		if ( 'gh-sales_page_ghsales-analytics' !== $hook ) {
			return;
		}

		// Enqueue CSS
		wp_enqueue_style(
			'ghsales-analytics',
			GHSALES_PLUGIN_URL . 'assets/css/analytics-dashboard.css',
			array(),
			GHSALES_VERSION
		);

		// Enqueue JS
		wp_enqueue_script(
			'ghsales-analytics',
			GHSALES_PLUGIN_URL . 'assets/js/analytics-dashboard.js',
			array( 'jquery' ),
			GHSALES_VERSION,
			true
		);

		// Pass AJAX URL to JS
		wp_localize_script(
			'ghsales-analytics',
			'ghsalesAnalytics',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ghsales_analytics' ),
			)
		);
	}

	/**
	 * Render analytics dashboard page
	 *
	 * @return void
	 */
	public static function render_analytics_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ghsales' ) );
		}

		// Get all dashboard data
		$overview_stats       = self::get_overview_stats();
		$activity_counts      = self::get_activity_counts();
		$top_viewed           = self::get_top_products( 'views' );
		$top_converting       = self::get_top_products( 'conversions' );
		$revenue_leaders      = self::get_top_products( 'revenue' );
		$trending             = self::get_top_products( 'trending' );
		$low_performing       = self::get_top_products( 'low_performing' );
		$search_analytics     = self::get_search_analytics();
		$active_sales         = self::get_active_sales_performance();
		$recent_activity      = self::get_recent_activity_feed();

		// Load dashboard template
		include GHSALES_PLUGIN_DIR . 'admin/partials/analytics-dashboard.php';
	}

	/**
	 * Get overview statistics for dashboard cards
	 *
	 * @return array Overview stats
	 */
	public static function get_overview_stats() {
		global $wpdb;

		// Get stats summary from Stats class
		$summary = GHSales_Stats::get_stats_summary();

		// Get activity counts for 7 days
		$activity_query = $wpdb->get_results(
			"SELECT activity_type, COUNT(*) as count
			FROM {$wpdb->prefix}ghsales_user_activity
			WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY activity_type"
		);

		// Convert to associative array
		$activities = array(
			'views'       => 0,
			'add_to_cart' => 0,
			'purchases'   => 0,
			'searches'    => 0,
		);

		foreach ( $activity_query as $row ) {
			if ( 'view' === $row->activity_type ) {
				$activities['views'] = (int) $row->count;
			} elseif ( 'add_to_cart' === $row->activity_type ) {
				$activities['add_to_cart'] = (int) $row->count;
			} elseif ( 'purchase' === $row->activity_type ) {
				$activities['purchases'] = (int) $row->count;
			} elseif ( 'search' === $row->activity_type ) {
				$activities['searches'] = (int) $row->count;
			}
		}

		// Calculate conversion rate
		$conversion_rate = 0;
		if ( $activities['views'] > 0 ) {
			$conversion_rate = ( $activities['purchases'] / $activities['views'] ) * 100;
		}

		return array(
			'views'            => $activities['views'],
			'add_to_carts'     => $activities['add_to_cart'],
			'purchases'        => $activities['purchases'],
			'searches'         => $activities['searches'],
			'conversion_rate'  => round( $conversion_rate, 2 ),
			'revenue'          => isset( $summary['total_revenue'] ) ? floatval( $summary['total_revenue'] ) : 0,
			'products_tracked' => isset( $summary['products_tracked'] ) ? intval( $summary['products_tracked'] ) : 0,
		);
	}

	/**
	 * Get activity type breakdown counts
	 *
	 * @param int $days Number of days to look back (default: 7)
	 * @return array Activity counts by type
	 */
	public static function get_activity_counts( $days = 7 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT activity_type, COUNT(*) as count
				FROM {$wpdb->prefix}ghsales_user_activity
				WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
				GROUP BY activity_type
				ORDER BY count DESC",
				$days
			)
		);

		$counts = array();
		foreach ( $results as $row ) {
			$counts[ $row->activity_type ] = (int) $row->count;
		}

		return $counts;
	}

	/**
	 * Get top products by various metrics
	 *
	 * @param string $type Type: 'views', 'conversions', 'revenue', 'trending', 'low_performing'
	 * @param int    $limit Number of results
	 * @return array Product data with WooCommerce product objects
	 */
	public static function get_top_products( $type = 'views', $limit = 10 ) {
		global $wpdb;

		$products = array();

		switch ( $type ) {
			case 'views':
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT product_id, views_7days as metric_value
						FROM {$wpdb->prefix}ghsales_product_stats
						WHERE views_7days > 0
						ORDER BY views_7days DESC
						LIMIT %d",
						$limit
					)
				);
				break;

			case 'conversions':
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT
							product_id,
							conversions_7days as metric_value,
							CASE
								WHEN views_7days > 0 THEN (conversions_7days * 1.0 / views_7days) * 100
								ELSE 0
							END as conversion_rate
						FROM {$wpdb->prefix}ghsales_product_stats
						WHERE conversions_7days > 0
						ORDER BY conversion_rate DESC, conversions_7days DESC
						LIMIT %d",
						$limit
					)
				);
				break;

			case 'revenue':
				$results = GHSales_Stats::get_revenue_leaders( $limit );
				break;

			case 'trending':
				$results = GHSales_Tracker::get_trending_products( $limit );
				break;

			case 'low_performing':
				$results = GHSales_Stats::get_low_performing_products( $limit );
				break;

			default:
				return array();
		}

		// Enrich with WooCommerce product data
		foreach ( $results as $row ) {
			// Handle both objects and arrays from different methods
			$product_id = is_object( $row ) ? $row->product_id : $row['product_id'];

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			// Extract values handling both object and array formats
			$metric_value    = is_object( $row ) ? ( isset( $row->metric_value ) ? $row->metric_value : 0 ) : ( isset( $row['metric_value'] ) ? $row['metric_value'] : 0 );
			$conversion_rate = is_object( $row ) ? ( isset( $row->conversion_rate ) ? $row->conversion_rate : 0 ) : ( isset( $row['conversion_rate'] ) ? $row['conversion_rate'] : 0 );
			$views_7days     = is_object( $row ) ? ( isset( $row->views_7days ) ? $row->views_7days : 0 ) : ( isset( $row['views_7days'] ) ? $row['views_7days'] : 0 );
			$conversions_7d  = is_object( $row ) ? ( isset( $row->conversions_7days ) ? $row->conversions_7days : 0 ) : ( isset( $row['conversions_7days'] ) ? $row['conversions_7days'] : 0 );
			$trend_score     = is_object( $row ) ? ( isset( $row->trend_score ) ? $row->trend_score : 0 ) : ( isset( $row['trend_score'] ) ? $row['trend_score'] : 0 );

			$products[] = array(
				'id'              => $product_id,
				'name'            => $product->get_name(),
				'thumbnail'       => $product->get_image( array( 50, 50 ) ),
				'price'           => $product->get_price_html(),
				'permalink'       => $product->get_permalink(),
				'metric_value'    => $metric_value,
				'conversion_rate' => round( $conversion_rate, 2 ),
				'views'           => $views_7days,
				'conversions'     => $conversions_7d,
				'trend_score'     => round( $trend_score, 2 ),
			);
		}

		return $products;
	}

	/**
	 * Get search analytics data
	 *
	 * @param int $limit Number of results
	 * @return array Top search queries
	 */
	public static function get_search_analytics( $limit = 20 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					search_query,
					COUNT(*) as search_count
				FROM {$wpdb->prefix}ghsales_user_activity
				WHERE activity_type = 'search'
					AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
					AND search_query IS NOT NULL
					AND search_query != ''
				GROUP BY search_query
				ORDER BY search_count DESC
				LIMIT %d",
				$limit
			)
		);

		$searches = array();
		foreach ( $results as $row ) {
			$searches[] = array(
				'query' => $row->search_query,
				'count' => (int) $row->search_count,
			);
		}

		return $searches;
	}

	/**
	 * Get active sales performance metrics
	 *
	 * @return array Active sales with performance data
	 */
	public static function get_active_sales_performance() {
		global $wpdb;

		$now = current_time( 'mysql' );

		// Get active events
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.ID,
					p.post_title,
					pm1.meta_value as start_date,
					pm2.meta_value as end_date
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_ghsales_start_date'
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_ghsales_end_date'
				WHERE p.post_type = 'ghsales_event'
					AND p.post_status = 'publish'
					AND pm1.meta_value <= %s
					AND pm2.meta_value >= %s
				ORDER BY pm1.meta_value ASC",
				$now,
				$now
			)
		);

		$sales = array();
		foreach ( $events as $event ) {
			// Get activity during this sale
			$activity = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(DISTINCT CASE WHEN activity_type = 'view' THEN product_id END) as products_viewed,
						COUNT(DISTINCT CASE WHEN activity_type = 'purchase' THEN product_id END) as products_sold,
						COUNT(CASE WHEN activity_type = 'purchase' THEN 1 END) as total_purchases
					FROM {$wpdb->prefix}ghsales_user_activity
					WHERE timestamp BETWEEN %s AND %s",
					$event->start_date,
					current_time( 'mysql' )
				)
			);

			$sales[] = array(
				'id'              => $event->ID,
				'name'            => $event->post_title,
				'start_date'      => $event->start_date,
				'end_date'        => $event->end_date,
				'products_viewed' => (int) $activity->products_viewed,
				'products_sold'   => (int) $activity->products_sold,
				'total_purchases' => (int) $activity->total_purchases,
			);
		}

		return $sales;
	}

	/**
	 * Get recent activity feed
	 *
	 * @param int $limit Number of activities
	 * @return array Recent activity records
	 */
	public static function get_recent_activity_feed( $limit = 20 ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					a.activity_type,
					a.product_id,
					a.search_query,
					a.category_id,
					a.timestamp,
					a.user_id,
					p.post_title as product_name,
					u.display_name as user_name
				FROM {$wpdb->prefix}ghsales_user_activity a
				LEFT JOIN {$wpdb->posts} p ON a.product_id = p.ID
				LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
				ORDER BY a.timestamp DESC
				LIMIT %d",
				$limit
			)
		);

		$activities = array();
		foreach ( $results as $row ) {
			// Format activity message
			$customer = $row->user_name ? $row->user_name : 'Guest';
			$time_ago = human_time_diff( strtotime( $row->timestamp ), current_time( 'timestamp' ) );

			$message = '';
			switch ( $row->activity_type ) {
				case 'view':
					$message = sprintf( '%s viewed %s', $customer, $row->product_name );
					break;
				case 'add_to_cart':
					$message = sprintf( '%s added %s to cart', $customer, $row->product_name );
					break;
				case 'purchase':
					$message = sprintf( '%s purchased %s', $customer, $row->product_name );
					break;
				case 'search':
					$message = sprintf( '%s searched for "%s"', $customer, $row->search_query );
					break;
				case 'category_view':
					$term = get_term( $row->category_id );
					$message = sprintf( '%s viewed category: %s', $customer, $term ? $term->name : 'Unknown' );
					break;
			}

			$activities[] = array(
				'type'      => $row->activity_type,
				'message'   => $message,
				'time_ago'  => $time_ago,
				'timestamp' => $row->timestamp,
			);
		}

		return $activities;
	}

	/**
	 * AJAX handler for live activity feed refresh
	 *
	 * @return void
	 */
	public static function ajax_get_live_feed() {
		// Verify nonce
		check_ajax_referer( 'ghsales_analytics', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get fresh activity feed
		$activities = self::get_recent_activity_feed( 20 );

		wp_send_json_success( $activities );
	}

	/**
	 * Get dashicon name for activity type
	 *
	 * @param string $type Activity type
	 * @return string Dashicon name
	 */
	public static function get_activity_icon( $type ) {
		$icons = array(
			'view'          => 'visibility',
			'add_to_cart'   => 'cart',
			'purchase'      => 'yes-alt',
			'search'        => 'search',
			'category_view' => 'category',
		);

		return isset( $icons[ $type ] ) ? $icons[ $type ] : 'admin-generic';
	}
}
