<?php
/**
 * GHSales Sale Engine
 *
 * Core engine that applies sale discounts to WooCommerce cart and checkout.
 * Handles BOGO, percentage, fixed amount, and tiered discounts.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Sale_Engine Class
 *
 * Applies sale event discounts to WooCommerce products
 */
class GHSales_Sale_Engine {

	/**
	 * Active sale events cache
	 *
	 * @var array
	 */
	private static $active_events = null;

	/**
	 * Initialize the sale engine
	 * Hooks into WooCommerce cart and pricing
	 *
	 * @return void
	 */
	public static function init() {
		// Hook into WooCommerce cart to apply discounts
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply_cart_discounts' ), 10, 1 );

		// Add BOGO free items to cart automatically
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'handle_bogo_addition' ), 10, 6 );

		// Display discount information on cart/checkout
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'display_discounted_price' ), 10, 3 );

		// Show BOGO quantity information
		add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'display_bogo_quantity' ), 10, 3 );

		// Add BOGO info after quantity in cart
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'add_bogo_info_to_name' ), 10, 3 );

		// Show sale badges on product pages
		add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'custom_sale_badge' ), 10, 3 );

		// Clear events cache when events are updated
		add_action( 'save_post_ghsales_event', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * Get all currently active sale events
	 * Checks date ranges and returns events that should be running now
	 *
	 * @return array Array of active event objects with their rules
	 */
	public static function get_active_events() {
		// Return cached events if available
		if ( self::$active_events !== null ) {
			return self::$active_events;
		}

		global $wpdb;

		// Get current timestamp
		$now = current_time( 'mysql' );

		// Query for active events (published posts within date range)
		$events = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title,
					pm1.meta_value as start_date,
					pm2.meta_value as end_date,
					pm3.meta_value as color_scheme_id,
					pm4.meta_value as allow_stacking,
					pm5.meta_value as apply_on_sale_price
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_ghsales_start_date'
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_ghsales_end_date'
				LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_ghsales_color_scheme'
				LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_ghsales_allow_stacking'
				LEFT JOIN {$wpdb->postmeta} pm5 ON p.ID = pm5.post_id AND pm5.meta_key = '_ghsales_apply_on_sale_price'
				WHERE p.post_type = 'ghsales_event'
				AND p.post_status = 'publish'
				AND pm1.meta_value <= %s
				AND pm2.meta_value >= %s
				ORDER BY pm1.meta_value ASC",
				$now,
				$now
			)
		);

		// Load rules for each active event
		foreach ( $events as $event ) {
			$event->rules = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}ghsales_rules
					WHERE event_id = %d
					ORDER BY priority DESC",
					$event->ID
				)
			);
		}

		// Cache the results
		self::$active_events = $events;

		return $events;
	}

	/**
	 * Apply sale discounts to cart items
	 * Called by WooCommerce before calculating totals
	 *
	 * @param WC_Cart $cart WooCommerce cart object
	 * @return void
	 */
	public static function apply_cart_discounts( $cart ) {
		// Avoid infinite loops during calculation
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		// Get active sale events
		$active_events = self::get_active_events();

		if ( empty( $active_events ) ) {
			return; // No active sales
		}

		// Loop through cart items and apply discounts
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$product_id = $cart_item['product_id'];
			$variation_id = $cart_item['variation_id'];
			$actual_product_id = $variation_id ? $variation_id : $product_id;
			$quantity = $cart_item['quantity'];

			// Get original price
			$original_price = floatval( $product->get_price() );

			// Check if this item has BOGO discount
			if ( isset( $cart_item['ghsales_bogo'] ) ) {
				$bogo_data = $cart_item['ghsales_bogo'];
				$free_per_paid = $bogo_data['free_per_paid'];

				// Calculate: customer pays for X, gets X + free items
				// For 1+1: qty=1 means they pay for 1, get 2 total
				// For 1+2: qty=1 means they pay for 1, get 3 total
				$total_items_received = $quantity * ( 1 + $free_per_paid );

				// Calculate the effective price per item (spread cost across all items)
				$effective_price_per_item = $original_price / ( 1 + $free_per_paid );
				$product->set_price( $effective_price_per_item );

				// Store BOGO info for display
				$cart_item['ghsales_bogo_display'] = array(
					'original_price' => $original_price,
					'total_items' => $total_items_received,
					'free_per_paid' => $free_per_paid,
					'event_name' => $bogo_data['event_name'],
				);

				continue; // Skip other discount checks for BOGO items
			}

			// Find applicable discount for non-BOGO products
			$discount = self::find_best_discount( $actual_product_id, $active_events, $original_price );

			if ( $discount ) {
				// Apply the discount
				$new_price = self::calculate_discounted_price( $original_price, $discount );
				$product->set_price( $new_price );

				// Store discount info in cart item for display
				$cart_item['ghsales_discount'] = array(
					'original_price' => $original_price,
					'discounted_price' => $new_price,
					'discount_type' => $discount['type'],
					'discount_value' => $discount['value'],
					'event_name' => $discount['event_name'],
				);
			}
		}
	}

	/**
	 * Find the best applicable discount for a product
	 * Checks all active events and returns highest priority matching rule
	 *
	 * @param int   $product_id Product ID to check
	 * @param array $events Active sale events
	 * @param float $original_price Original product price
	 * @return array|null Discount info or null if no discount applies
	 */
	private static function find_best_discount( $product_id, $events, $original_price ) {
		$best_discount = null;
		$highest_priority = -1;

		// Get product categories and tags for matching
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );

		// Loop through all active events
		foreach ( $events as $event ) {
			if ( empty( $event->rules ) ) {
				continue;
			}

			// Check each rule in this event
			foreach ( $event->rules as $rule ) {
				// Skip BOGO rules for now (handled separately)
				if ( $rule->rule_type === 'bogo' || $rule->rule_type === 'buy_x_get_y' ) {
					continue;
				}

				// Check if rule applies to this product
				$applies = false;

				switch ( $rule->applies_to ) {
					case 'all':
						$applies = true;
						break;

					case 'products':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = in_array( $product_id, $target_ids );
						break;

					case 'categories':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = ! empty( array_intersect( $product_categories, $target_ids ) );
						break;

					case 'tags':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = ! empty( array_intersect( $product_tags, $target_ids ) );
						break;
				}

				// If rule applies and has higher priority, use it
				if ( $applies && $rule->priority > $highest_priority ) {
					$best_discount = array(
						'type' => $rule->rule_type,
						'value' => floatval( $rule->discount_value ),
						'event_name' => $event->post_title,
						'priority' => $rule->priority,
					);
					$highest_priority = $rule->priority;
				}
			}
		}

		return $best_discount;
	}

	/**
	 * Calculate discounted price based on discount type
	 *
	 * @param float $original_price Original product price
	 * @param array $discount Discount information
	 * @return float New discounted price
	 */
	private static function calculate_discounted_price( $original_price, $discount ) {
		switch ( $discount['type'] ) {
			case 'percentage':
				// Percentage discount (e.g., 20% off)
				$discount_amount = ( $original_price * $discount['value'] ) / 100;
				return max( 0, $original_price - $discount_amount );

			case 'fixed':
				// Fixed amount discount (e.g., €10 off)
				return max( 0, $original_price - $discount['value'] );

			default:
				return $original_price;
		}
	}

	/**
	 * Handle BOGO discount when item is added to cart
	 * Marks items with BOGO data instead of adding separate free items
	 *
	 * @param string $cart_item_key Cart item key
	 * @param int    $product_id Product ID
	 * @param int    $quantity Quantity added
	 * @param int    $variation_id Variation ID
	 * @param array  $variation Variation data
	 * @param array  $cart_item_data Cart item data
	 * @return void
	 */
	public static function handle_bogo_addition( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		// Get active events
		$active_events = self::get_active_events();

		if ( empty( $active_events ) ) {
			return;
		}

		$actual_product_id = $variation_id ? $variation_id : $product_id;

		// Find BOGO rules that apply to this product
		$bogo_rule = self::find_bogo_rule( $actual_product_id, $active_events );

		if ( $bogo_rule ) {
			// Mark this cart item as BOGO (we'll handle pricing in apply_cart_discounts)
			WC()->cart->cart_contents[ $cart_item_key ]['ghsales_bogo'] = array(
				'free_per_paid' => intval( $bogo_rule['free_items'] ),
				'event_name' => $bogo_rule['event_name'],
			);
		}
	}

	/**
	 * Find applicable BOGO rule for a product
	 *
	 * @param int   $product_id Product ID
	 * @param array $events Active events
	 * @return array|null BOGO rule info or null
	 */
	private static function find_bogo_rule( $product_id, $events ) {
		$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );

		$highest_priority = -1;
		$best_rule = null;

		foreach ( $events as $event ) {
			if ( empty( $event->rules ) ) {
				continue;
			}

			foreach ( $event->rules as $rule ) {
				// Only check BOGO rules
				if ( $rule->rule_type !== 'bogo' ) {
					continue;
				}

				// Check if rule applies
				$applies = false;

				switch ( $rule->applies_to ) {
					case 'all':
						$applies = true;
						break;

					case 'products':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = in_array( $product_id, $target_ids );
						break;

					case 'categories':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = ! empty( array_intersect( $product_categories, $target_ids ) );
						break;

					case 'tags':
						$target_ids = array_map( 'intval', explode( ',', $rule->target_ids ) );
						$applies = ! empty( array_intersect( $product_tags, $target_ids ) );
						break;
				}

				if ( $applies && $rule->priority > $highest_priority ) {
					$best_rule = array(
						'free_items' => intval( $rule->discount_value ),
						'event_name' => $event->post_title,
					);
					$highest_priority = $rule->priority;
				}
			}
		}

		return $best_rule;
	}

	/**
	 * Display discounted price in cart
	 *
	 * @param string $price_html Price HTML
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 * @return string Modified price HTML
	 */
	public static function display_discounted_price( $price_html, $cart_item, $cart_item_key ) {
		// Show BOGO pricing
		if ( isset( $cart_item['ghsales_bogo_display'] ) ) {
			$bogo = $cart_item['ghsales_bogo_display'];
			$free_per_paid = $bogo['free_per_paid'];

			return sprintf(
				'%s<br><small class="ghsales-bogo-label" style="color: #46b450; font-weight: bold;">%s</small>',
				wc_price( $bogo['original_price'] ),
				sprintf( __( '1+%d FREE', 'ghsales' ), $free_per_paid )
			);
		}

		// Show original + discounted price if discount applied
		if ( isset( $cart_item['ghsales_discount'] ) ) {
			$discount = $cart_item['ghsales_discount'];
			return sprintf(
				'<del>%s</del> <ins>%s</ins><br><small class="ghsales-sale-label">%s</small>',
				wc_price( $discount['original_price'] ),
				wc_price( $discount['discounted_price'] ),
				sprintf( __( 'Sale: %s', 'ghsales' ), esc_html( $discount['event_name'] ) )
			);
		}

		return $price_html;
	}

	/**
	 * Custom sale badge for products
	 *
	 * @param string     $html Sale flash HTML
	 * @param WP_Post    $post Post object
	 * @param WC_Product $product Product object
	 * @return string Modified sale badge HTML
	 */
	public static function custom_sale_badge( $html, $post, $product ) {
		$product_id = $product->get_id();

		// Check if product has active sale
		$active_events = self::get_active_events();
		$discount = self::find_best_discount( $product_id, $active_events, $product->get_price() );

		if ( $discount ) {
			return '<span class="onsale ghsales-badge">' . esc_html( $discount['event_name'] ) . '</span>';
		}

		return $html;
	}

	/**
	 * Display BOGO quantity information
	 *
	 * @param string $quantity_html Quantity HTML
	 * @param string $cart_item_key Cart item key
	 * @param array  $cart_item Cart item data
	 * @return string Modified quantity HTML
	 */
	public static function display_bogo_quantity( $quantity_html, $cart_item_key, $cart_item ) {
		if ( isset( $cart_item['ghsales_bogo_display'] ) ) {
			$bogo = $cart_item['ghsales_bogo_display'];
			$quantity = $cart_item['quantity'];
			$total_items = $bogo['total_items'];
			$free_per_paid = $bogo['free_per_paid'];

			// Show: Aantal: X (Ontvangt: Y) with 1+1 FREE label
			return sprintf(
				'%s<br><small style="color: #46b450; font-weight: bold;">%s: %d (%d+%d FREE)</small>',
				$quantity_html,
				__( 'Ontvangt', 'ghsales' ),
				$total_items,
				$quantity,
				$quantity * $free_per_paid
			);
		}

		return $quantity_html;
	}

	/**
	 * Add BOGO badge to product name in cart
	 *
	 * @param string $name Product name HTML
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 * @return string Modified name HTML
	 */
	public static function add_bogo_info_to_name( $name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['ghsales_bogo_display'] ) ) {
			$bogo = $cart_item['ghsales_bogo_display'];
			$free_per_paid = $bogo['free_per_paid'];
			$quantity = $cart_item['quantity'];
			$total_received = $bogo['total_items'];

			// Add BOGO badge AND quantity data attribute for JavaScript
			$name .= sprintf(
				' <span class="ghsales-bogo-badge" data-free-per-paid="%d" data-paid-qty="%d" data-total-qty="%d" style="background: #46b450; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; margin-left: 8px;">1+%d FREE</span>',
				$free_per_paid,
				$quantity,
				$total_received,
				$free_per_paid
			);
		}

		return $name;
	}

	/**
	 * Clear cached active events
	 * Called when sale events are updated
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$active_events = null;
	}
}
