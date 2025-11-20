<?php
/**
 * GHSales Upsell Recommendation Engine
 *
 * Intelligent product recommendation system for increasing average order value.
 * Generates recommendations based on:
 * - Price psychology (25-50% of cart/product value)
 * - Category matching
 * - Frequently bought together patterns
 * - User browsing history
 * - Product popularity and trends
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Upsell Class
 *
 * Handles all upsell recommendation logic and caching
 */
class GHSales_Upsell {

	/**
	 * Cache duration in seconds (1 hour)
	 *
	 * @var int
	 */
	const CACHE_DURATION = 3600;

	/**
	 * Price ratio bounds for psychological pricing
	 * Recommendations should be 25-50% of base price
	 *
	 * @var float
	 */
	const MIN_PRICE_RATIO = 0.25;
	const MAX_PRICE_RATIO = 0.50;

	/**
	 * Maximum products to recommend
	 *
	 * @var int
	 */
	const MAX_RECOMMENDATIONS = 6;

	/**
	 * Initialize upsell system
	 * Sets up WordPress hooks for displaying recommendations
	 *
	 * @return void
	 */
	public static function init() {
		// Mini cart integration (ghminicart plugin)
		add_action( 'ghminicart_sale_section_content', array( __CLASS__, 'render_cart_upsells' ) );

		// Product page integration (optional hook - can also use shortcode)
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'render_product_upsells' ), 15 );

		// Register generic upsells shortcode only (homepage/product use gulcan-plugins widget)
		add_shortcode( 'ghsales_upsells', array( __CLASS__, 'upsells_shortcode' ) );

		// AJAX handlers for dynamic loading
		add_action( 'wp_ajax_ghsales_get_upsells', array( __CLASS__, 'ajax_get_upsells' ) );
		add_action( 'wp_ajax_nopriv_ghsales_get_upsells', array( __CLASS__, 'ajax_get_upsells' ) );

		// Cleanup expired cache (daily cron)
		add_action( 'ghsales_cleanup_expired_cache', array( __CLASS__, 'cleanup_expired_cache' ) );

		// Cache invalidation on cart changes
		// Clear cache when items are added to cart
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'clear_cart_cache' ), 10, 0 );
		// Clear cache when items are removed from cart
		add_action( 'woocommerce_cart_item_removed', array( __CLASS__, 'clear_cart_cache' ), 10, 0 );
		// Clear cache when cart is emptied
		add_action( 'woocommerce_cart_emptied', array( __CLASS__, 'clear_cart_cache' ), 10, 0 );
		// Clear cache when quantity changes to zero
		add_action( 'woocommerce_before_cart_item_quantity_zero', array( __CLASS__, 'clear_cart_cache' ), 10, 0 );
		// Clear cache on cart update (quantity changes)
		add_action( 'woocommerce_update_cart_action_cart_updated', array( __CLASS__, 'clear_cart_cache' ), 10, 0 );
	}

	/**
	 * Get product recommendations for a given context
	 *
	 * @param string $context_type Context: 'cart', 'product', 'homepage'
	 * @param int    $context_id Product ID (for product context) or null
	 * @param array  $args Additional arguments (limit, exclude_ids, etc.)
	 * @return array Array of recommended product IDs with scores
	 */
	public static function get_recommendations( $context_type, $context_id = null, $args = array() ) {
		// Default arguments
		$defaults = array(
			'limit'       => self::MAX_RECOMMENDATIONS,
			'exclude_ids' => array(),
			'min_score'   => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		// Check cache first
		$cached = self::get_cached_recommendations( $context_type, $context_id );
		if ( $cached !== false ) {
			return array_slice( $cached, 0, $args['limit'] );
		}

		// Generate fresh recommendations based on context
		$recommendations = array();

		switch ( $context_type ) {
			case 'cart':
				$recommendations = self::generate_cart_recommendations( $args );
				break;

			case 'product':
				$recommendations = self::generate_product_recommendations( $context_id, $args );
				break;

			case 'homepage':
				$recommendations = self::generate_homepage_recommendations( $args );
				break;

			default:
				$recommendations = self::generate_generic_recommendations( $args );
				break;
		}

		// Filter by minimum score and exclude IDs
		$recommendations = array_filter(
			$recommendations,
			function( $item ) use ( $args ) {
				return $item['score'] >= $args['min_score'] && ! in_array( $item['product_id'], $args['exclude_ids'] );
			}
		);

		// Sort by score (descending)
		usort(
			$recommendations,
			function( $a, $b ) {
				return $b['score'] - $a['score'];
			}
		);

		// Limit results
		$recommendations = array_slice( $recommendations, 0, $args['limit'] );

		// Cache the results
		self::cache_recommendations( $context_type, $context_id, $recommendations );

		return $recommendations;
	}

	/**
	 * Generate cart-specific recommendations
	 * Based on: cart contents, price psychology, categories, frequently bought together
	 *
	 * @param array $args Arguments
	 * @return array Recommendations with scores
	 */
	private static function generate_cart_recommendations( $args ) {
		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			// Empty cart - show popular products
			return self::generate_generic_recommendations( $args );
		}

		$recommendations = array();
		$cart_total      = floatval( $cart->get_subtotal() );
		$cart_products   = array();
		$cart_categories = array();

		// Analyze cart contents
		foreach ( $cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];
			$cart_products[] = $product_id;

			// Get product categories
			$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			$cart_categories = array_merge( $cart_categories, $categories );
		}

		$cart_categories = array_unique( $cart_categories );

		// Exclude cart products from recommendations
		$args['exclude_ids'] = array_merge( $args['exclude_ids'], $cart_products );

		// Get products that are frequently bought together
		$frequently_bought = self::get_frequently_bought_together( $cart_products, $args );
		$recommendations = array_merge( $recommendations, $frequently_bought );

		// Get products in same categories (with price psychology)
		$category_matches = self::get_category_matches( $cart_categories, $cart_total, $args );
		$recommendations = array_merge( $recommendations, $category_matches );

		// Get trending/popular products (as fallback)
		$popular = self::get_popular_products_with_score( 10, $args );
		$recommendations = array_merge( $recommendations, $popular );

		// Merge scores for duplicate products
		$recommendations = self::merge_duplicate_scores( $recommendations );

		return $recommendations;
	}

	/**
	 * Generate product page recommendations
	 * Based on: current product, category, price, user history
	 *
	 * @param int   $product_id Product ID
	 * @param array $args Arguments
	 * @return array Recommendations with scores
	 */
	private static function generate_product_recommendations( $product_id, $args ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$recommendations = array();
		$product_price   = floatval( $product->get_price() );
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		// Exclude current product
		$args['exclude_ids'][] = $product_id;

		// Get frequently bought together with this product
		$frequently_bought = self::get_frequently_bought_together( array( $product_id ), $args );
		$recommendations = array_merge( $recommendations, $frequently_bought );

		// Get products in same categories (with price psychology)
		$category_matches = self::get_category_matches( $product_categories, $product_price, $args );
		$recommendations = array_merge( $recommendations, $category_matches );

		// Get complementary products (different category, suitable price)
		$complementary = self::get_complementary_products( $product_id, $product_price, $args );
		$recommendations = array_merge( $recommendations, $complementary );

		// Merge duplicate scores
		$recommendations = self::merge_duplicate_scores( $recommendations );

		return $recommendations;
	}

	/**
	 * Generate homepage recommendations
	 * Based on: user history, popular products, trending products
	 *
	 * @param array $args Arguments
	 * @return array Recommendations with scores
	 */
	private static function generate_homepage_recommendations( $args ) {
		$recommendations = array();

		// Get user's recent activity (viewed products)
		$user_activity = self::get_user_recent_activity( 30 ); // Last 30 days
		if ( ! empty( $user_activity ) ) {
			$viewed_products = wp_list_pluck( $user_activity, 'product_id' );
			$viewed_categories = array();

			foreach ( $viewed_products as $pid ) {
				$cats = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) );
				$viewed_categories = array_merge( $viewed_categories, $cats );
			}
			$viewed_categories = array_unique( $viewed_categories );

			// Get products from viewed categories
			$personalized = self::get_category_matches( $viewed_categories, 0, $args );
			$recommendations = array_merge( $recommendations, $personalized );
		}

		// Add trending products
		$trending = self::get_trending_products_with_score( 10, $args );
		$recommendations = array_merge( $recommendations, $trending );

		// Add popular products
		$popular = self::get_popular_products_with_score( 10, $args );
		$recommendations = array_merge( $recommendations, $popular );

		// Merge duplicate scores
		$recommendations = self::merge_duplicate_scores( $recommendations );

		return $recommendations;
	}

	/**
	 * Generate generic recommendations (fallback)
	 * Based on: popular and trending products
	 *
	 * @param array $args Arguments
	 * @return array Recommendations with scores
	 */
	private static function generate_generic_recommendations( $args ) {
		$recommendations = array();

		// Get popular products
		$popular = self::get_popular_products_with_score( 10, $args );
		$recommendations = array_merge( $recommendations, $popular );

		// Get trending products
		$trending = self::get_trending_products_with_score( 10, $args );
		$recommendations = array_merge( $recommendations, $trending );

		// Merge duplicate scores
		$recommendations = self::merge_duplicate_scores( $recommendations );

		return $recommendations;
	}

	/**
	 * Get products frequently bought together with given products
	 *
	 * @param array $product_ids Array of product IDs
	 * @param array $args Arguments (exclude_ids)
	 * @return array Products with scores
	 */
	private static function get_frequently_bought_together( $product_ids, $args ) {
		global $wpdb;

		if ( empty( $product_ids ) ) {
			return array();
		}

		// Query: Find products purchased in same orders
		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$exclude_placeholders = ! empty( $args['exclude_ids'] ) ? implode( ',', array_fill( 0, count( $args['exclude_ids'] ), '%d' ) ) : '';

		$query = "
			SELECT oi2.product_id, COUNT(DISTINCT oi1.order_id) as purchase_count
			FROM {$wpdb->prefix}woocommerce_order_items oi1
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim1 ON oi1.order_item_id = oim1.order_item_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi2 ON oi1.order_id = oi2.order_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON oi2.order_item_id = oim2.order_item_id
			WHERE oi1.order_item_type = 'line_item'
			  AND oi2.order_item_type = 'line_item'
			  AND oim1.meta_key = '_product_id'
			  AND oim2.meta_key = '_product_id'
			  AND oim1.meta_value IN ($placeholders)
			  AND oim2.meta_value NOT IN ($placeholders)
		";

		$params = array_merge( $product_ids, $product_ids );

		if ( ! empty( $exclude_placeholders ) ) {
			$query .= " AND oim2.meta_value NOT IN ($exclude_placeholders)";
			$params = array_merge( $params, $args['exclude_ids'] );
		}

		$query .= " GROUP BY oi2.product_id ORDER BY purchase_count DESC LIMIT 20";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		// Convert to scored format
		$recommendations = array();
		$max_count = ! empty( $results ) ? intval( $results[0]->purchase_count ) : 1;

		foreach ( $results as $row ) {
			// Score: High for frequently bought together (80-100 points)
			$score = 80 + ( 20 * ( intval( $row->purchase_count ) / $max_count ) );

			$recommendations[] = array(
				'product_id' => intval( $row->product_id ),
				'score'      => $score,
				'reason'     => 'frequently_bought_together',
			);
		}

		return $recommendations;
	}

	/**
	 * Get products in matching categories with price psychology
	 *
	 * @param array $category_ids Category IDs
	 * @param float $base_price Base price for ratio calculation
	 * @param array $args Arguments (exclude_ids)
	 * @return array Products with scores
	 */
	private static function get_category_matches( $category_ids, $base_price, $args ) {
		if ( empty( $category_ids ) ) {
			return array();
		}

		// Calculate price range (25-50% of base price)
		$min_price = $base_price * self::MIN_PRICE_RATIO;
		$max_price = $base_price * self::MAX_PRICE_RATIO;

		// Query products in matching categories
		$query_args = array(
			'status'      => 'publish',
			'limit'       => 20,
			'orderby'     => 'popularity',
			'order'       => 'DESC',
			'category'    => $category_ids,
			'exclude'     => $args['exclude_ids'],
		);

		// Add price filter if base price is set
		if ( $base_price > 0 ) {
			$query_args['price'] = array( 'min' => $min_price, 'max' => $max_price );
		}

		$products = wc_get_products( $query_args );

		$recommendations = array();
		foreach ( $products as $product ) {
			$product_price = floatval( $product->get_price() );

			// Calculate price ratio score
			$price_ratio_score = 0;
			if ( $base_price > 0 ) {
				$ratio = $product_price / $base_price;
				if ( $ratio >= self::MIN_PRICE_RATIO && $ratio <= self::MAX_PRICE_RATIO ) {
					// Perfect price range: 60-70 points
					$price_ratio_score = 60 + ( 10 * ( 1 - abs( $ratio - 0.375 ) / 0.125 ) );
				} else {
					// Outside ideal range: 40-60 points
					$price_ratio_score = 40;
				}
			} else {
				// No base price: default score
				$price_ratio_score = 50;
			}

			$recommendations[] = array(
				'product_id' => $product->get_id(),
				'score'      => $price_ratio_score,
				'reason'     => 'category_match',
			);
		}

		return $recommendations;
	}

	/**
	 * Get complementary products (different categories, suitable price)
	 *
	 * @param int   $product_id Base product ID
	 * @param float $base_price Base product price
	 * @param array $args Arguments (exclude_ids)
	 * @return array Products with scores
	 */
	private static function get_complementary_products( $product_id, $base_price, $args ) {
		// Get base product categories
		$base_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

		// Get all product categories except base
		$all_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'exclude'    => $base_categories,
				'fields'     => 'ids',
			)
		);

		if ( empty( $all_categories ) ) {
			return array();
		}

		// Calculate price range
		$min_price = $base_price * self::MIN_PRICE_RATIO;
		$max_price = $base_price * self::MAX_PRICE_RATIO;

		// Query products
		$query_args = array(
			'status'      => 'publish',
			'limit'       => 15,
			'orderby'     => 'popularity',
			'order'       => 'DESC',
			'category'    => $all_categories,
			'exclude'     => $args['exclude_ids'],
		);

		if ( $base_price > 0 ) {
			$query_args['price'] = array( 'min' => $min_price, 'max' => $max_price );
		}

		$products = wc_get_products( $query_args );

		$recommendations = array();
		foreach ( $products as $product ) {
			// Complementary products get moderate scores: 40-50 points
			$recommendations[] = array(
				'product_id' => $product->get_id(),
				'score'      => 45,
				'reason'     => 'complementary',
			);
		}

		return $recommendations;
	}

	/**
	 * Get popular products with scores
	 *
	 * @param int   $limit Number of products
	 * @param array $args Arguments (exclude_ids)
	 * @return array Products with scores
	 */
	private static function get_popular_products_with_score( $limit, $args ) {
		$popular = GHSales_Tracker::get_popular_products( '7days', $limit );

		$recommendations = array();

		// Safety check: ensure we have valid data before processing
		if ( empty( $popular ) || ! is_array( $popular ) ) {
			return $recommendations;
		}

		// Get max views for normalization, ensuring it's never zero
		$max_views = ! empty( $popular[0]['views'] ) ? intval( $popular[0]['views'] ) : 1;
		$max_views = max( 1, $max_views ); // Ensure at least 1 to prevent division by zero

		foreach ( $popular as $index => $item ) {
			// Skip if views key doesn't exist
			if ( ! isset( $item['views'] ) || ! isset( $item['product_id'] ) ) {
				continue;
			}

			$product_id = intval( $item['product_id'] );

			if ( in_array( $product_id, $args['exclude_ids'] ) ) {
				continue;
			}

			// Popular products: 30-50 points (based on view count)
			// Ensure views is at least 0 to prevent errors
			$views = max( 0, intval( $item['views'] ) );
			$score = 30 + ( 20 * ( $views / $max_views ) );

			$recommendations[] = array(
				'product_id' => $product_id,
				'score'      => $score,
				'reason'     => 'popular',
			);
		}

		return $recommendations;
	}

	/**
	 * Get trending products with scores
	 *
	 * @param int   $limit Number of products
	 * @param array $args Arguments (exclude_ids)
	 * @return array Products with scores
	 */
	private static function get_trending_products_with_score( $limit, $args ) {
		$trending = GHSales_Tracker::get_trending_products( $limit );

		$recommendations = array();
		$max_score = ! empty( $trending ) ? floatval( $trending[0]['trend_score'] ) : 1;

		foreach ( $trending as $item ) {
			$product_id = intval( $item['product_id'] );

			if ( in_array( $product_id, $args['exclude_ids'] ) ) {
				continue;
			}

			// Trending products: 40-60 points (based on trend score)
			$score = 40 + ( 20 * ( floatval( $item['trend_score'] ) / $max_score ) );

			$recommendations[] = array(
				'product_id' => $product_id,
				'score'      => $score,
				'reason'     => 'trending',
			);
		}

		return $recommendations;
	}

	/**
	 * Get user's recent activity
	 *
	 * @param int $days Number of days to look back
	 * @return array Activity records
	 */
	private static function get_user_recent_activity( $days = 30 ) {
		$user_id = get_current_user_id();
		$session_id = GHSales_Tracker::get_session_id();

		return GHSales_Tracker::get_recent_activity( $user_id, $session_id, $days );
	}

	/**
	 * Merge duplicate product scores (sum scores for same product)
	 *
	 * @param array $recommendations Recommendations array
	 * @return array Merged recommendations
	 */
	private static function merge_duplicate_scores( $recommendations ) {
		$merged = array();

		foreach ( $recommendations as $item ) {
			$product_id = $item['product_id'];

			if ( isset( $merged[ $product_id ] ) ) {
				// Add scores together
				$merged[ $product_id ]['score'] += $item['score'];
			} else {
				$merged[ $product_id ] = $item;
			}
		}

		return array_values( $merged );
	}

	/**
	 * Get cached recommendations
	 *
	 * @param string $context_type Context type
	 * @param int    $context_id Context ID
	 * @return array|false Cached recommendations or false
	 */
	private static function get_cached_recommendations( $context_type, $context_id ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$session_id = GHSales_Tracker::get_session_id();

		$cache = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT recommended_products, expires_at
				FROM {$wpdb->prefix}ghsales_upsell_cache
				WHERE context_type = %s
				  AND context_id = %d
				  AND (user_id = %d OR session_id = %s)
				  AND expires_at > NOW()
				ORDER BY created_at DESC
				LIMIT 1",
				$context_type,
				$context_id ? $context_id : 0,
				$user_id ? $user_id : 0,
				$session_id
			)
		);

		if ( $cache ) {
			return json_decode( $cache->recommended_products, true );
		}

		return false;
	}

	/**
	 * Cache recommendations
	 *
	 * @param string $context_type Context type
	 * @param int    $context_id Context ID
	 * @param array  $recommendations Recommendations to cache
	 * @return bool Success
	 */
	private static function cache_recommendations( $context_type, $context_id, $recommendations ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$session_id = GHSales_Tracker::get_session_id();

		return $wpdb->insert(
			$wpdb->prefix . 'ghsales_upsell_cache',
			array(
				'context_type'         => $context_type,
				'context_id'           => $context_id ? $context_id : 0,
				'user_id'              => $user_id ? $user_id : null,
				'session_id'           => $session_id,
				'recommended_products' => wp_json_encode( $recommendations ),
				'expires_at'           => gmdate( 'Y-m-d H:i:s', time() + self::CACHE_DURATION ),
				'created_at'           => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Cleanup expired cache entries
	 * Runs daily via cron
	 *
	 * @return void
	 */
	public static function cleanup_expired_cache() {
		global $wpdb;

		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->prefix}ghsales_upsell_cache WHERE expires_at < NOW()"
		);

		if ( $deleted ) {
			error_log( "GHSales: Cleaned up {$deleted} expired upsell cache entries" );
		}
	}

	/**
	 * Clear cart context cache for current user/session
	 * Called when cart contents change to ensure fresh recommendations
	 *
	 * @return void
	 */
	public static function clear_cart_cache() {
		global $wpdb;

		$user_id = get_current_user_id();
		$session_id = GHSales_Tracker::get_session_id();

		// Delete cart-context cache for current user/session
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}ghsales_upsell_cache
				WHERE context_type = 'cart'
				AND (user_id = %d OR session_id = %s)",
				$user_id ? $user_id : 0,
				$session_id
			)
		);

		if ( $deleted ) {
			error_log( "GHSales: Cleared cart cache for user {$user_id} / session {$session_id} - {$deleted} entries deleted" );
		}
	}

	/**
	 * Render cart upsells in mini cart
	 * Hooked to: ghminicart_sale_section_content
	 *
	 * @return void
	 */
	public static function render_cart_upsells() {
		// First, show special sales (BOGO, discounts)
		$special_sale_product_ids = self::render_special_sales();

		// Then show upsell recommendations with Swiper
		// Exclude products that are already shown in special sales
		$args = array(
			'limit'       => 4,
			'exclude_ids' => ! empty( $special_sale_product_ids ) ? $special_sale_product_ids : array(),
		);
		$recommendations = self::get_recommendations( 'cart', null, $args );

		if ( empty( $recommendations ) ) {
			return;
		}

		// Generate unique ID for this carousel instance
		static $carousel_counter = 0;
		$carousel_counter++;
		$widget_id = 'ghsales-minicart-upsells-' . $carousel_counter;

		// Get product data for each recommendation
		$products_data = array();
		foreach ( $recommendations as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( $product ) {
				$products_data[] = self::format_product_for_display( $product );
			}
		}

		if ( empty( $products_data ) ) {
			return;
		}

		// Render with Swiper carousel (same as gulcan-plugins)
		?>
		<div class="ghsales-cart-upsells-section">
			<h3 class="ghsales-upsells-title">Misschien vind je dit ook leuk</h3>

			<div id="<?php echo esc_attr( $widget_id ); ?>" class="ghsales-cart-upsells gulcan-wc-products-carousel">
				<div class="gulcan-products-swiper swiper">
					<div class="swiper-wrapper">
						<?php foreach ( $products_data as $product_data ) : ?>
							<div class="swiper-slide">
								<?php self::render_product_card( $product_data ); ?>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Progress bar pagination -->
					<div class="swiper-pagination"></div>
				</div>
			</div>

			<script>
			// Initialize Swiper for minicart upsells
			document.addEventListener('DOMContentLoaded', function() {
				if (typeof Swiper !== 'undefined') {
					new Swiper('#<?php echo esc_js( $widget_id ); ?> .swiper', {
						slidesPerView: 2,
						spaceBetween: 16,
						centeredSlides: false,
						slidesPerGroup: 1,
						loop: false,
						pagination: {
							el: '#<?php echo esc_js( $widget_id ); ?> .swiper-pagination',
							type: 'progressbar',
							clickable: false
						},
						breakpoints: {
							768: {
								slidesPerView: 3,
								spaceBetween: 16
							},
							1024: {
								slidesPerView: 2,
								spaceBetween: 16
							}
						}
					});
				}
			});
			</script>
		</div>
		<?php
	}

	/**
	 * Render special sales section (BOGO, discounts)
	 * Shows products with active sales at the top of minicart
	 *
	 * @return array Array of product IDs shown in special sales (for exclusion from upsells)
	 */
	private static function render_special_sales() {
		// Check if GHSales Sale Engine is available
		if ( ! class_exists( 'GHSales_Sale_Engine' ) ) {
			return array();
		}

		// Get active sale events
		$active_events = GHSales_Sale_Engine::get_active_events();

		if ( empty( $active_events ) ) {
			return array();
		}

		// Get products for each sale event
		$sale_products = array();
		foreach ( $active_events as $event ) {
			error_log( 'GHSales Special Sales: Checking event - ' . $event->post_title );

			// Get products from this event's rules
			if ( ! empty( $event->rules ) ) {
				foreach ( $event->rules as $rule ) {
					error_log( 'GHSales Special Sales: Rule found - type: ' . $rule->rule_type . ', applies_to: ' . $rule->applies_to );
					error_log( 'GHSales Special Sales: target_ids = ' . $rule->target_ids );

					// Get target IDs from the rule
					$target_ids = ! empty( $rule->target_ids ) ? explode( ',', $rule->target_ids ) : array();
					$target_ids = array_map( 'intval', array_filter( $target_ids ) );

					if ( empty( $target_ids ) ) {
						error_log( 'GHSales Special Sales: No target IDs found in rule' );
						continue;
					}

					// Get products based on applies_to field
					if ( $rule->applies_to === 'products' ) {
						// Get products by IDs
						foreach ( $target_ids as $product_id ) {
							$product = wc_get_product( $product_id );
							if ( $product && $product->is_in_stock() ) {
								$sale_products[ $product_id ] = array(
									'product'    => $product,
									'event_name' => $event->post_title,
								);
								error_log( 'GHSales Special Sales: Added product ID ' . $product_id . ' - ' . $product->get_name() );
							}
						}
					} elseif ( $rule->applies_to === 'categories' ) {
						// Get products by categories
						$args = array(
							'status'       => 'publish',
							'category'     => $target_ids,
							'limit'        => 4,
							'stock_status' => 'instock',
						);
						$products = wc_get_products( $args );
						foreach ( $products as $product ) {
							$sale_products[ $product->get_id() ] = array(
								'product'    => $product,
								'event_name' => $event->post_title,
							);
							error_log( 'GHSales Special Sales: Added product from category - ' . $product->get_name() );
						}
					}

					// Stop if we have enough products
					if ( count( $sale_products ) >= 4 ) {
						break 2;
					}
				}
			}
		}

		// If no products found, don't show the section
		error_log( 'GHSales Special Sales: Total products found = ' . count( $sale_products ) );
		if ( empty( $sale_products ) ) {
			error_log( 'GHSales Special Sales: No products found - returning empty array' );
			return array();
		}

		// Limit to 4 products max
		$sale_products = array_slice( $sale_products, 0, 4, true );

		// Get product IDs for exclusion from upsells
		$product_ids_shown = array_keys( $sale_products );

		// Check if we have multiple sale events
		$event_names = array_unique( array_column( $sale_products, 'event_name' ) );
		$multiple_sales = count( $event_names ) > 1;

		// Generate unique ID for carousel
		static $carousel_counter = 0;
		$carousel_counter++;
		$widget_id = 'ghsales-special-sales-' . $carousel_counter;

		// Format products for display
		$products_data = array();
		foreach ( $sale_products as $sale_product ) {
			$products_data[] = self::format_product_for_display( $sale_product['product'] );
		}

		// Render special sales section
		?>
		<div class="ghsales-special-sales-section <?php echo $multiple_sales ? 'multiple-sales' : ''; ?>">
			<h3 class="ghsales-special-sales-title">Speciale Aanbiedingen</h3>

			<?php if ( ! $multiple_sales && count( $event_names ) === 1 ) : ?>
				<div class="ghsales-special-sale-event-title"><?php echo esc_html( $event_names[0] ); ?></div>
			<?php endif; ?>

			<div id="<?php echo esc_attr( $widget_id ); ?>" class="ghsales-special-sale-products gulcan-wc-products-carousel">
				<div class="gulcan-products-swiper swiper">
					<div class="swiper-wrapper">
						<?php foreach ( $products_data as $product_data ) : ?>
							<div class="swiper-slide">
								<?php self::render_product_card( $product_data ); ?>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Progress bar pagination -->
					<div class="swiper-pagination"></div>
				</div>
			</div>

			<script>
			// Initialize Swiper for special sales
			document.addEventListener('DOMContentLoaded', function() {
				if (typeof Swiper !== 'undefined') {
					new Swiper('#<?php echo esc_js( $widget_id ); ?> .swiper', {
						slidesPerView: 2,
						spaceBetween: 16,
						centeredSlides: false,
						slidesPerGroup: 1,
						loop: false,
						pagination: {
							el: '#<?php echo esc_js( $widget_id ); ?> .swiper-pagination',
							type: 'progressbar',
							clickable: false
						},
						breakpoints: {
							768: {
								slidesPerView: 3,
								spaceBetween: 16
							},
							1024: {
								slidesPerView: 2,
								spaceBetween: 16
							}
						}
					});
				}
			});
			</script>
		</div>
		<?php

		// Return product IDs for exclusion from upsells
		return $product_ids_shown;
	}

	/**
	 * Render product page upsells
	 * Hooked to: woocommerce_after_single_product_summary
	 *
	 * @return void
	 */
	public static function render_product_upsells() {
		global $product;

		if ( ! $product ) {
			return;
		}

		$recommendations = self::get_recommendations( 'product', $product->get_id(), array( 'limit' => 6 ) );

		if ( empty( $recommendations ) ) {
			return;
		}

		echo '<div class="ghsales-product-upsells">';
		echo '<h2>' . esc_html__( 'Complete Your Purchase', 'ghsales' ) . '</h2>';
		echo '<div class="ghsales-upsells-carousel">';

		foreach ( $recommendations as $item ) {
			self::render_upsell_product( $item['product_id'] );
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Format product data for frontend display (same as gulcan-plugins)
	 *
	 * @param WC_Product $product WooCommerce product object
	 * @return array Formatted product data
	 */
	private static function format_product_for_display( $product ) {
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return array();
		}

		// Get product image
		$image_id = $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : wc_placeholder_img_src( 'woocommerce_single' );

		// Get product pricing
		$price_html = $product->get_price_html();
		if ( empty( $price_html ) ) {
			$price_html = $product->is_type( 'variable' )
				? '<span class="price-range">' . __( 'Price varies', 'ghsales' ) . '</span>'
				: '<span class="no-price">' . __( 'Price not available', 'ghsales' ) . '</span>';
		}

		// Get rating
		$rating_count = $product->get_rating_count();
		$average_rating = $product->get_average_rating();

		// Get brand/category
		$brand_name = '';
		$brand_attribute = $product->get_attribute( 'pa_brand' );
		if ( ! empty( $brand_attribute ) ) {
			$brand_name = $brand_attribute;
		} else {
			$tags = wp_get_post_terms( $product->get_id(), 'product_tag' );
			if ( ! empty( $tags ) ) {
				$brand_name = $tags[0]->name;
			} else {
				$categories = wp_get_post_terms( $product->get_id(), 'product_cat' );
				if ( ! empty( $categories ) ) {
					foreach ( $categories as $category ) {
						$cat_name_lower = strtolower( $category->name );
						if ( ! in_array( $cat_name_lower, array( 'product', 'products', 'uncategorized', 'algemeen', 'shop' ) ) ) {
							$brand_name = $category->name;
							break;
						}
					}
				}
			}
		}

		// Get variation text for variable products
		$variation_text = '';
		if ( $product->is_type( 'variable' ) ) {
			$attributes = $product->get_variation_attributes();
			$attribute_names = array();
			foreach ( $attributes as $attribute_name => $options ) {
				if ( $attribute_name !== 'pa_brand' ) {
					$label = wc_attribute_label( $attribute_name );
					if ( in_array( strtolower( $label ), array( 'color', 'colour', 'size', 'material' ) ) ) {
						$attribute_names[] = $label;
					}
				}
			}
			if ( ! empty( $attribute_names ) ) {
				$variation_text = implode( ', ', $attribute_names );
			}
		}

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'permalink'         => $product->get_permalink(),
			'image_url'         => $image_url,
			'price_html'        => $price_html,
			'is_on_sale'        => $product->is_on_sale(),
			'is_new_product'    => false, // Don't show NEW badges in minicart
			'rating_count'      => $rating_count,
			'average_rating'    => $average_rating,
			'add_to_cart_url'   => $product->add_to_cart_url(),
			'brand_name'        => $brand_name,
			'variation_text'    => $variation_text,
		);
	}

	/**
	 * Render product card (same as gulcan-plugins)
	 *
	 * @param array $product_data Product data array
	 * @return void
	 */
	private static function render_product_card( $product_data ) {
		if ( empty( $product_data ) ) {
			return;
		}
		?>
		<div class="gulcan-product-card">
			<div class="gulcan-product-image-container">
				<a href="<?php echo esc_url( $product_data['permalink'] ); ?>" class="gulcan-product-image-link">
					<img src="<?php echo esc_url( $product_data['image_url'] ); ?>"
						 alt="<?php echo esc_attr( $product_data['name'] ); ?>"
						 class="gulcan-product-image">
				</a>
				<?php
				// Show sale badges (BOGO, percentage, fixed discounts)
				$product = wc_get_product( $product_data['id'] );
				if ( $product ) {
					$ghsales_badge = apply_filters( 'woocommerce_sale_flash', '', get_post( $product_data['id'] ), $product );
					if ( ! empty( $ghsales_badge ) ) {
						echo $ghsales_badge;
					} elseif ( $product_data['is_on_sale'] ) {
						echo '<span class="gulcan-product-sale">' . __( 'Sale', 'ghsales' ) . '</span>';
					}
				} elseif ( $product_data['is_on_sale'] ) {
					echo '<span class="gulcan-product-sale">' . __( 'Sale', 'ghsales' ) . '</span>';
				}
				?>
				<!-- Quick Add to Cart Button (ghsales AJAX handler) -->
				<button class="ghsales-upsell-add-to-cart"
						data-product-id="<?php echo esc_attr( $product_data['id'] ); ?>"
						data-product-url="<?php echo esc_url( $product_data['add_to_cart_url'] ); ?>"
						title="<?php esc_attr_e( 'Quick Add to Cart', 'ghsales' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
						<path d="M292.31-115.38q-25.31 0-42.66-17.35-17.34-17.35-17.34-42.65 0-25.31 17.34-42.66 17.35-17.34 42.66-17.34 25.31 0 42.65 17.34 17.35 17.35 17.35 42.66 0 25.3-17.35 42.65-17.34 17.35-42.65 17.35Zm375.38 0q-25.31 0-42.65-17.35-17.35-17.35-17.35-42.65 0-25.31 17.35-42.66 17.34-17.34 42.65-17.34t42.66 17.34q17.34 17.35 17.34 42.66 0 25.3-17.34 42.65-17.35 17.35-42.66 17.35ZM235.23-740 342-515.38h265.38q6.93 0 12.31-3.47 5.39-3.46 9.23-9.61l104.62-190q4.61-8.46.77-15-3.85-6.54-13.08-6.54h-486Zm-19.54-40h520.77q26.08 0 39.23 21.27 13.16 21.27 1.39 43.81l-114.31 208.3q-8.69 14.62-22.58 22.93-13.88 8.31-30.5 8.31H324l-48.62 89.23q-6.15 9.23-.38 20 5.77 10.77 17.31 10.77h435.38v40H292.31q-35 0-52.23-29.5-17.23-29.5-.85-59.27l60.15-107.23L152.31-820H80v-40h97.69l38 80ZM342-515.38h280-280Z"/>
					</svg>
				</button>
			</div>

			<div class="gulcan-product-content">
				<!-- Brand/Category name -->
				<?php if ( ! empty( $product_data['brand_name'] ) ) : ?>
					<div class="gulcan-product-meta">
						<span class="gulcan-product-category">
							<?php
							echo esc_html( strtoupper( $product_data['brand_name'] ) );
							if ( ! empty( $product_data['variation_text'] ) ) {
								echo ' | ' . esc_html( strtoupper( $product_data['variation_text'] ) );
							}
							?>
						</span>
					</div>
				<?php endif; ?>

				<!-- Review stars -->
				<div class="gulcan-product-rating">
					<div class="gulcan-stars">
						<?php if ( $product_data['rating_count'] > 0 ) : ?>
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<?php if ( $i <= $product_data['average_rating'] ) : ?>
									<svg xmlns="http://www.w3.org/2000/svg" height="14px" viewBox="0 -960 960 960" width="14px" fill="#ffd700" class="gulcan-star filled">
										<path d="m243-144 63-266L96-589l276-24 108-251 108 252 276 23-210 179 63 266-237-141-237 141Z"/>
									</svg>
								<?php else : ?>
									<svg xmlns="http://www.w3.org/2000/svg" height="14px" viewBox="0 -960 960 960" width="14px" fill="#e3e3e3" class="gulcan-star empty">
										<path d="m352-293 128-76 129 76-34-144 111-95-147-13-59-137-59 137-147 13 112 95-34 144ZM243-144l63-266L96-589l276-24 108-251 108 252 276 23-210 179 63 266-237-141-237 141Zm237-333Z"/>
									</svg>
								<?php endif; ?>
							<?php endfor; ?>
							<span class="gulcan-review-count">(<?php echo $product_data['rating_count']; ?>)</span>
						<?php else : ?>
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<svg xmlns="http://www.w3.org/2000/svg" height="14px" viewBox="0 -960 960 960" width="14px" fill="#e3e3e3" class="gulcan-star empty">
									<path d="m352-293 128-76 129 76-34-144 111-95-147-13-59-137-59 137-147 13 112 95-34 144ZM243-144l63-266L96-589l276-24 108-251 108 252 276 23-210 179 63 266-237-141-237 141Zm237-333Z"/>
								</svg>
							<?php endfor; ?>
						<?php endif; ?>
					</div>
				</div>

				<p class="gulcan-product-title">
					<a href="<?php echo esc_url( $product_data['permalink'] ); ?>">
						<?php echo esc_html( $product_data['name'] ); ?>
					</a>
				</p>

				<div class="gulcan-product-price">
					<?php echo $product_data['price_html']; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for getting upsells
	 *
	 * @return void
	 */
	public static function ajax_get_upsells() {
		check_ajax_referer( 'ghsales_upsell_nonce', 'nonce' );

		$context_type = isset( $_POST['context_type'] ) ? sanitize_text_field( $_POST['context_type'] ) : 'generic';
		$context_id   = isset( $_POST['context_id'] ) ? absint( $_POST['context_id'] ) : null;
		$limit        = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 4;

		$recommendations = self::get_recommendations( $context_type, $context_id, array( 'limit' => $limit ) );

		wp_send_json_success(
			array(
				'recommendations' => $recommendations,
				'count'           => count( $recommendations ),
			)
		);
	}

	/**
	 * Generic upsells shortcode
	 *
	 * Usage: [ghsales_upsells context="cart" limit="4" title="You May Also Like"]
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public static function upsells_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'context'    => 'generic',
				'context_id' => null,
				'limit'      => 4,
				'title'      => __( 'Recommended Products', 'ghsales' ),
				'columns'    => 4,
			),
			$atts,
			'ghsales_upsells'
		);

		$recommendations = self::get_recommendations(
			$atts['context'],
			$atts['context_id'],
			array( 'limit' => absint( $atts['limit'] ) )
		);

		if ( empty( $recommendations ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="ghsales-upsells-shortcode">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h2 class="ghsales-upsells-title"><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php endif; ?>
			<div class="ghsales-upsells-grid" data-columns="<?php echo esc_attr( $atts['columns'] ); ?>">
				<?php foreach ( $recommendations as $item ) : ?>
					<?php self::render_upsell_product( $item['product_id'] ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get recommendation IDs for gulcan-plugins integration
	 *
	 * Returns just the product IDs for the gulcan-plugins product widget
	 * Context is determined automatically based on the page type
	 *
	 * @param int $limit Number of products to return
	 * @return array Array of product IDs
	 */
	public static function get_recommendation_ids( $limit = 8 ) {
		global $product;

		// Determine context automatically
		$context_type = 'homepage';
		$context_id   = null;

		if ( is_product() && $product ) {
			// Product page context
			$context_type = 'product';
			$context_id   = $product->get_id();
		} elseif ( function_exists( 'is_cart' ) && is_cart() ) {
			// Cart page context
			$context_type = 'cart';
		}

		// Get recommendations
		$recommendations = self::get_recommendations(
			$context_type,
			$context_id,
			array( 'limit' => absint( $limit ) )
		);

		// Extract just the product IDs
		$product_ids = array();
		foreach ( $recommendations as $item ) {
			$product_ids[] = $item['product_id'];
		}

		return $product_ids;
	}
}
