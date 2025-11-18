<?php
/**
 * GHSales User Tracker
 *
 * Tracks user behavior for personalization and analytics.
 * Tracks: product views, category browsing, searches, add-to-cart, purchases.
 *
 * Tracks by default without consent checks (external plugins handle consent).
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Tracker Class
 *
 * Handles all user activity tracking
 */
class GHSales_Tracker {

	/**
	 * Initialize tracker
	 * Sets up WordPress and WooCommerce hooks
	 *
	 * @return void
	 */
	public static function init() {
		// Product view tracking
		add_action( 'woocommerce_before_single_product', array( __CLASS__, 'track_product_view' ) );

		// Add to cart tracking
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'track_add_to_cart' ), 10, 6 );

		// Purchase tracking
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'track_purchase' ), 10, 1 );

		// Search tracking
		add_action( 'pre_get_posts', array( __CLASS__, 'track_search' ) );

		// Category view tracking
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'track_category_view' ) );
	}

	/**
	 * Track product view
	 * Fires when user views a product page
	 *
	 * @return void
	 */
	public static function track_product_view() {
		// Get current product ID
		$product_id = get_the_ID();

		if ( ! $product_id ) {
			return;
		}

		// Track the view
		self::track_activity(
			'view',
			array(
				'product_id' => $product_id,
			)
		);

		// Update product stats (increment view counter)
		self::increment_product_stat( $product_id, 'view' );
	}

	/**
	 * Track add to cart event
	 *
	 * @param string $cart_item_key Cart item key
	 * @param int    $product_id Product ID
	 * @param int    $quantity Quantity added
	 * @param int    $variation_id Variation ID (0 if simple product)
	 * @param array  $variation Variation data
	 * @param array  $cart_item_data Cart item data
	 * @return void
	 */
	public static function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		// Use variation ID if available, otherwise product ID
		$tracked_product_id = $variation_id > 0 ? $variation_id : $product_id;

		// Track the activity
		self::track_activity(
			'add_to_cart',
			array(
				'product_id' => $tracked_product_id,
				'meta_data'  => wp_json_encode(
					array(
						'quantity'     => $quantity,
						'variation_id' => $variation_id,
						'parent_id'    => $product_id,
					)
				),
			)
		);
	}

	/**
	 * Track purchase (order completion)
	 *
	 * @param int $order_id WooCommerce order ID
	 * @return void
	 */
	public static function track_purchase( $order_id ) {
		// Get order
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Track each product in the order
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$quantity   = $item->get_quantity();
			$total      = $item->get_total();

			// Track purchase activity
			self::track_activity(
				'purchase',
				array(
					'product_id' => $product_id,
					'meta_data'  => wp_json_encode(
						array(
							'quantity'  => $quantity,
							'total'     => $total,
							'order_id'  => $order_id,
						)
					),
				)
			);

			// Update product stats (increment conversion counter)
			self::increment_product_stat( $product_id, 'conversion', $total );
		}
	}

	/**
	 * Track search query
	 *
	 * @param WP_Query $query WordPress query object
	 * @return void
	 */
	public static function track_search( $query ) {
		// Only track search on main query and product search
		if ( ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		// Get search query
		$search_query = $query->get( 's' );

		if ( empty( $search_query ) ) {
			return;
		}

		// Track the search
		self::track_activity(
			'search',
			array(
				'search_query' => sanitize_text_field( $search_query ),
			)
		);
	}

	/**
	 * Track category view
	 * Fires when user browses a product category
	 *
	 * @return void
	 */
	public static function track_category_view() {
		// Check if we're on a category page
		if ( ! is_product_category() ) {
			return;
		}

		// Get current category
		$category = get_queried_object();

		if ( ! $category || ! isset( $category->term_id ) ) {
			return;
		}

		// Track category view
		self::track_activity(
			'category_view',
			array(
				'category_id' => $category->term_id,
			)
		);
	}

	/**
	 * Track generic activity to database
	 *
	 * @param string $activity_type Type of activity (view, search, add_to_cart, purchase, etc.)
	 * @param array  $data Activity data (product_id, category_id, search_query, meta_data)
	 * @return int|false Insert ID on success, false on failure
	 */
	private static function track_activity( $activity_type, $data = array() ) {
		global $wpdb;

		// Prepare activity record
		$activity = array(
			'session_id'     => self::get_session_id(),
			'user_id'        => self::get_user_id(),
			'activity_type'  => $activity_type,
			'product_id'     => isset( $data['product_id'] ) ? absint( $data['product_id'] ) : null,
			'category_id'    => isset( $data['category_id'] ) ? absint( $data['category_id'] ) : null,
			'search_query'   => isset( $data['search_query'] ) ? sanitize_text_field( $data['search_query'] ) : null,
			'meta_data'      => isset( $data['meta_data'] ) ? $data['meta_data'] : null,
			'ip_address'     => self::mask_ip( self::get_ip_address() ),
			'user_agent'     => self::get_user_agent(),
			'timestamp'      => current_time( 'mysql' ),
		);

		// Insert into database
		$result = $wpdb->insert(
			$wpdb->prefix . 'ghsales_user_activity',
			$activity,
			array( '%s', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Increment product stat counter
	 * Updates wp_ghsales_product_stats table
	 *
	 * @param int    $product_id Product ID
	 * @param string $stat_type Type: 'view' or 'conversion'
	 * @param float  $revenue Revenue amount (for conversions)
	 * @return void
	 */
	private static function increment_product_stat( $product_id, $stat_type, $revenue = 0.0 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ghsales_product_stats';

		// Check if product stats exist
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT product_id FROM {$table} WHERE product_id = %d",
				$product_id
			)
		);

		if ( ! $exists ) {
			// Create initial stats record
			$wpdb->insert(
				$table,
				array(
					'product_id'   => $product_id,
					'last_updated' => current_time( 'mysql' ),
				),
				array( '%d', '%s' )
			);
		}

		// Build update query based on stat type
		if ( $stat_type === 'view' ) {
			// Increment view counters
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET
						views_total = views_total + 1,
						views_7days = views_7days + 1,
						views_30days = views_30days + 1,
						last_updated = %s
					WHERE product_id = %d",
					current_time( 'mysql' ),
					$product_id
				)
			);
		} elseif ( $stat_type === 'conversion' ) {
			// Increment conversion counters and revenue
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET
						conversions_total = conversions_total + 1,
						conversions_7days = conversions_7days + 1,
						revenue_total = revenue_total + %f,
						last_updated = %s
					WHERE product_id = %d",
					$revenue,
					current_time( 'mysql' ),
					$product_id
				)
			);
		}
	}

	/**
	 * Get recent activity for a user or session
	 *
	 * @param string $session_id Session ID (optional)
	 * @param int    $user_id User ID (optional)
	 * @param int    $limit Number of records to return (default: 50)
	 * @return array Activity records
	 */
	public static function get_recent_activity( $session_id = null, $user_id = null, $limit = 50 ) {
		global $wpdb;

		$where = array( '1=1' );
		$params = array();

		if ( $session_id ) {
			$where[] = 'session_id = %s';
			$params[] = $session_id;
		}

		if ( $user_id ) {
			$where[] = 'user_id = %d';
			$params[] = $user_id;
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT * FROM {$wpdb->prefix}ghsales_user_activity
				  WHERE {$where_clause}
				  ORDER BY timestamp DESC
				  LIMIT %d";

		$params[] = $limit;

		return $wpdb->get_results(
			$wpdb->prepare( $query, $params ),
			ARRAY_A
		);
	}

	/**
	 * Get popular products based on views
	 *
	 * @param int    $limit Number of products to return (default: 10)
	 * @param string $timeframe Timeframe: 'total', '7days', '30days' (default: '7days')
	 * @return array Product IDs with view counts
	 */
	public static function get_popular_products( $limit = 10, $timeframe = '7days' ) {
		global $wpdb;

		// Determine which column to use
		$column_map = array(
			'total'  => 'views_total',
			'7days'  => 'views_7days',
			'30days' => 'views_30days',
		);

		$column = isset( $column_map[ $timeframe ] ) ? $column_map[ $timeframe ] : 'views_7days';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, {$column} as view_count
				FROM {$wpdb->prefix}ghsales_product_stats
				WHERE {$column} > 0
				ORDER BY {$column} DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get trending products (high recent views, low overall views)
	 *
	 * @param int $limit Number of products to return (default: 10)
	 * @return array Product IDs with trend scores
	 */
	public static function get_trending_products( $limit = 10 ) {
		global $wpdb;

		// Trending score = (7-day views / total views) * 100
		// Higher score = trending up
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id,
					CASE
						WHEN views_total > 0 THEN (views_7days * 1.0 / views_total) * 100
						ELSE 0
					END as trend_score
				FROM {$wpdb->prefix}ghsales_product_stats
				WHERE views_7days > 0
				ORDER BY trend_score DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get WooCommerce session ID
	 * Returns unique identifier for tracking guest users
	 *
	 * @return string Session ID
	 */
	public static function get_session_id() {
		// Try WooCommerce session first
		if ( function_exists( 'WC' ) && WC()->session ) {
			$session_id = WC()->session->get_customer_id();
			if ( $session_id ) {
				return $session_id;
			}
		}

		// Fallback to WordPress session cookie
		if ( isset( $_COOKIE['wp_woocommerce_session_'] ) ) {
			return sanitize_text_field( $_COOKIE['wp_woocommerce_session_'] );
		}

		// Last resort: generate unique ID based on IP and user agent
		return 'guest_' . md5( self::get_ip_address() . self::get_user_agent() );
	}

	/**
	 * Get current user ID
	 * Returns WordPress user ID or null for guests
	 *
	 * @return int|null User ID or null
	 */
	private static function get_user_id() {
		$user_id = get_current_user_id();
		return $user_id > 0 ? $user_id : null;
	}

	/**
	 * Get user's IP address
	 * Checks various headers for proxy/CDN scenarios
	 *
	 * @return string IP address
	 */
	private static function get_ip_address() {
		// Check for proxy headers (in order of reliability)
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				// For X-Forwarded-For, take the first IP
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				return $ip;
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Mask IP address for GDPR compliance
	 * Removes last octet of IPv4 or last 80 bits of IPv6
	 *
	 * @param string $ip IP address to mask
	 * @return string Masked IP address
	 */
	private static function mask_ip( $ip ) {
		// IPv6 check
		if ( strpos( $ip, ':' ) !== false ) {
			// For IPv6, mask last 80 bits (keep first 48 bits)
			$parts = explode( ':', $ip );
			return implode( ':', array_slice( $parts, 0, 3 ) ) . '::0';
		}

		// IPv4 - mask last octet
		$parts = explode( '.', $ip );
		if ( count( $parts ) === 4 ) {
			$parts[3] = '0';
			return implode( '.', $parts );
		}

		return $ip;
	}

	/**
	 * Get user agent string
	 *
	 * @return string User agent
	 */
	private static function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	}
}
