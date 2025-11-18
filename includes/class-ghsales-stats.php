<?php
/**
 * GHSales Product Stats Aggregator
 *
 * Manages product statistics aggregation and cleanup.
 * Handles cron jobs for resetting time-based counters.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Stats Class
 *
 * Aggregates and manages product statistics
 */
class GHSales_Stats {

	/**
	 * Initialize stats manager
	 * Sets up cron jobs and hooks
	 *
	 * @return void
	 */
	public static function init() {
		// Schedule cron jobs on plugin activation (done in installer)
		// Register cron hooks
		add_action( 'ghsales_reset_weekly_stats', array( __CLASS__, 'reset_weekly_stats' ) );
		add_action( 'ghsales_reset_monthly_stats', array( __CLASS__, 'reset_monthly_stats' ) );
		add_action( 'ghsales_cleanup_old_activity', array( __CLASS__, 'cleanup_old_activity' ) );
		add_action( 'ghsales_cleanup_expired_cache', array( __CLASS__, 'cleanup_expired_cache' ) );
	}

	/**
	 * Schedule cron jobs
	 * Called during plugin activation
	 *
	 * @return void
	 */
	public static function schedule_cron_jobs() {
		// Reset 7-day stats (weekly on Monday at 00:00)
		if ( ! wp_next_scheduled( 'ghsales_reset_weekly_stats' ) ) {
			wp_schedule_event( strtotime( 'next Monday' ), 'weekly', 'ghsales_reset_weekly_stats' );
		}

		// Reset 30-day stats (monthly on 1st at 00:00)
		if ( ! wp_next_scheduled( 'ghsales_reset_monthly_stats' ) ) {
			wp_schedule_event( strtotime( 'first day of next month' ), 'monthly', 'ghsales_reset_monthly_stats' );
		}

		// Cleanup old activity (daily at 03:00)
		if ( ! wp_next_scheduled( 'ghsales_cleanup_old_activity' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 03:00' ), 'daily', 'ghsales_cleanup_old_activity' );
		}

		// Cleanup expired cache (daily at 04:00)
		if ( ! wp_next_scheduled( 'ghsales_cleanup_expired_cache' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 04:00' ), 'daily', 'ghsales_cleanup_expired_cache' );
		}
	}

	/**
	 * Unschedule all cron jobs
	 * Called during plugin deactivation
	 *
	 * @return void
	 */
	public static function unschedule_cron_jobs() {
		$cron_hooks = array(
			'ghsales_reset_weekly_stats',
			'ghsales_reset_monthly_stats',
			'ghsales_cleanup_old_activity',
			'ghsales_cleanup_expired_cache',
		);

		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Reset 7-day statistics
	 * Runs weekly (every Monday)
	 *
	 * @return void
	 */
	public static function reset_weekly_stats() {
		global $wpdb;

		// Reset views_7days and conversions_7days to 0
		$wpdb->query(
			"UPDATE {$wpdb->prefix}ghsales_product_stats
			SET views_7days = 0, conversions_7days = 0"
		);

		error_log( 'GHSales: Reset weekly stats (7-day counters)' );
	}

	/**
	 * Reset 30-day statistics
	 * Runs monthly (1st of each month)
	 *
	 * @return void
	 */
	public static function reset_monthly_stats() {
		global $wpdb;

		// Reset views_30days to 0
		$wpdb->query(
			"UPDATE {$wpdb->prefix}ghsales_product_stats
			SET views_30days = 0"
		);

		error_log( 'GHSales: Reset monthly stats (30-day counters)' );
	}

	/**
	 * Cleanup old user activity
	 * Deletes activity records older than 1 year
	 * Runs daily
	 *
	 * @return void
	 */
	public static function cleanup_old_activity() {
		global $wpdb;

		// Delete activity older than 1 year
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ghsales_user_activity
				WHERE timestamp < %s",
				date( 'Y-m-d H:i:s', strtotime( '-1 year' ) )
			)
		);

		if ( $deleted ) {
			error_log( "GHSales: Cleaned up {$deleted} old activity records (>1 year)" );
		}
	}

	/**
	 * Cleanup expired upsell cache
	 * Deletes cache entries past their expiration time
	 * Runs daily
	 *
	 * @return void
	 */
	public static function cleanup_expired_cache() {
		global $wpdb;

		// Delete expired cache entries
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ghsales_upsell_cache
				WHERE expires_at < %s",
				current_time( 'mysql' )
			)
		);

		if ( $deleted ) {
			error_log( "GHSales: Cleaned up {$deleted} expired upsell cache entries" );
		}
	}

	/**
	 * Get product stats
	 *
	 * @param int $product_id Product ID
	 * @return object|null Product stats or null if not found
	 */
	public static function get_product_stats( $product_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ghsales_product_stats WHERE product_id = %d",
				$product_id
			)
		);
	}

	/**
	 * Get conversion rate for a product
	 *
	 * @param int    $product_id Product ID
	 * @param string $timeframe Timeframe: 'total' or '7days' (default: '7days')
	 * @return float Conversion rate (0-100)
	 */
	public static function get_conversion_rate( $product_id, $timeframe = '7days' ) {
		$stats = self::get_product_stats( $product_id );

		if ( ! $stats ) {
			return 0.0;
		}

		if ( $timeframe === '7days' ) {
			$views = $stats->views_7days;
			$conversions = $stats->conversions_7days;
		} else {
			$views = $stats->views_total;
			$conversions = $stats->conversions_total;
		}

		// Avoid division by zero
		if ( $views === 0 ) {
			return 0.0;
		}

		return round( ( $conversions / $views ) * 100, 2 );
	}

	/**
	 * Get top converting products
	 *
	 * @param int    $limit Number of products to return (default: 10)
	 * @param string $timeframe Timeframe: 'total' or '7days' (default: '7days')
	 * @return array Product IDs with conversion rates
	 */
	public static function get_top_converting_products( $limit = 10, $timeframe = '7days' ) {
		global $wpdb;

		if ( $timeframe === '7days' ) {
			$views_column = 'views_7days';
			$conversions_column = 'conversions_7days';
		} else {
			$views_column = 'views_total';
			$conversions_column = 'conversions_total';
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id,
					{$views_column} as views,
					{$conversions_column} as conversions,
					CASE
						WHEN {$views_column} > 0 THEN ({$conversions_column} * 1.0 / {$views_column}) * 100
						ELSE 0
					END as conversion_rate
				FROM {$wpdb->prefix}ghsales_product_stats
				WHERE {$views_column} > 0
				ORDER BY conversion_rate DESC, {$conversions_column} DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get revenue leaders (products with highest revenue)
	 *
	 * @param int $limit Number of products to return (default: 10)
	 * @return array Product IDs with revenue
	 */
	public static function get_revenue_leaders( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, revenue_total
				FROM {$wpdb->prefix}ghsales_product_stats
				WHERE revenue_total > 0
				ORDER BY revenue_total DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Update profit margin for a product
	 * This can be set manually or calculated from WooCommerce cost plugins
	 *
	 * @param int   $product_id Product ID
	 * @param float $profit_margin Profit margin percentage (0-100)
	 * @return bool True on success, false on failure
	 */
	public static function update_profit_margin( $product_id, $profit_margin ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'ghsales_product_stats',
			array( 'profit_margin' => $profit_margin ),
			array( 'product_id' => $product_id ),
			array( '%f' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get products with low performance (candidates for discounting)
	 *
	 * @param int $limit Number of products to return (default: 20)
	 * @return array Product IDs with performance scores
	 */
	public static function get_low_performing_products( $limit = 20 ) {
		global $wpdb;

		// Low performance = low conversions in last 7 days but some views
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id,
					views_7days,
					conversions_7days,
					CASE
						WHEN views_7days > 0 THEN (conversions_7days * 1.0 / views_7days) * 100
						ELSE 0
					END as conversion_rate
				FROM {$wpdb->prefix}ghsales_product_stats
				WHERE views_7days >= 10 AND conversions_7days < 2
				ORDER BY views_7days DESC, conversion_rate ASC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get stats summary for admin dashboard
	 *
	 * @return array Summary statistics
	 */
	public static function get_stats_summary() {
		global $wpdb;

		$table = $wpdb->prefix . 'ghsales_product_stats';

		return array(
			'total_views_7days'       => $wpdb->get_var( "SELECT SUM(views_7days) FROM {$table}" ),
			'total_conversions_7days' => $wpdb->get_var( "SELECT SUM(conversions_7days) FROM {$table}" ),
			'total_revenue'           => $wpdb->get_var( "SELECT SUM(revenue_total) FROM {$table}" ),
			'products_tracked'        => $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE views_total > 0" ),
			'avg_conversion_rate'     => $wpdb->get_var(
				"SELECT AVG(
					CASE
						WHEN views_7days > 0 THEN (conversions_7days * 1.0 / views_7days) * 100
						ELSE 0
					END
				) FROM {$table} WHERE views_7days > 0"
			),
		);
	}
}
