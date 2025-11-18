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

		// Add regular discount badge to cart item name
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'add_discount_badge_to_name' ), 15, 3 );

		// Show sale badges on product pages
		add_filter( 'woocommerce_sale_flash', array( __CLASS__, 'custom_sale_badge' ), 10, 3 );

		// Display limit reached message
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'display_limit_message' ), 20, 3 );

		// Track purchases when order is completed
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'track_order_purchases' ) );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'track_order_purchases' ) );

		// Save BOGO data to order item meta
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_bogo_to_order_item' ), 10, 4 );

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

			error_log( 'GHSales: Processing cart item - product_id=' . $actual_product_id . ', qty=' . $quantity );

			// Get original price (regular price, not sale price)
			$wc_product = wc_get_product( $actual_product_id );
			$regular_price = floatval( $wc_product->get_regular_price() );
			$current_price = floatval( $product->get_price() );
			$is_on_sale = $wc_product->is_on_sale();

			// Determine which price to use based on "apply_on_sale_price" setting
			// We'll check this per event below
			$original_price = $current_price;

			// Check if this item has BOGO discount
			error_log( 'Has ghsales_bogo in cart_item: ' . ( isset( $cart_item['ghsales_bogo'] ) ? 'YES' : 'NO' ) );
			if ( isset( $cart_item['ghsales_bogo'] ) ) {
				$bogo_data = $cart_item['ghsales_bogo'];
				$free_per_paid = $bogo_data['free_per_paid'];
				$allow_stacking = isset( $bogo_data['allow_stacking'] ) ? $bogo_data['allow_stacking'] : false;
				$apply_on_sale_price = isset( $bogo_data['apply_on_sale_price'] ) ? $bogo_data['apply_on_sale_price'] : false;

				error_log( 'GHSales: is_on_sale=' . ( $is_on_sale ? 'YES' : 'NO' ) . ', apply_on_sale_price=' . ( $apply_on_sale_price ? 'YES' : 'NO' ) );

				// Check purchase limit if quantity changed in cart
				if ( ! empty( $bogo_data['max_quantity'] ) ) {
					$limit_check = self::check_purchase_limit(
						$bogo_data['rule_id'],
						$bogo_data['max_quantity'],
						$quantity
					);

					// If limit exceeded, auto-reduce quantity to maximum allowed
					if ( ! $limit_check['allowed'] ) {
						$max_allowed = $limit_check['remaining'] + $limit_check['purchased'];
						error_log( 'GHSales: Purchase limit reached (qty=' . $quantity . ', limit=' . $bogo_data['max_quantity'] . ') - auto-reducing to ' . $max_allowed );

						// Set quantity to maximum allowed
						WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $max_allowed;
						$quantity = $max_allowed; // Update local variable too

						// Show warning that we reduced the quantity
						WC()->cart->cart_contents[ $cart_item_key ]['ghsales_limit_reached'] = array(
							'remaining' => 0, // No more remaining after this
							'max_quantity' => $bogo_data['max_quantity'],
						);
					}
				}

				// Check if product is on WooCommerce sale and if we should skip it
				if ( $is_on_sale && ! $apply_on_sale_price ) {
					error_log( 'GHSales: Product is on WC sale but apply_on_sale_price is OFF - skipping BOGO' );
					// Product is on sale, but event says don't apply to sale items
					// Skip this BOGO and treat as normal item
					unset( WC()->cart->cart_contents[ $cart_item_key ]['ghsales_bogo'] );
					continue;
				}

				// Calculate: customer pays for X, gets X + free items
				// For 1+1: qty=1 means they pay full price for 1, get 1 free (2 total)
				// For 1+2: qty=1 means they pay full price for 1, get 2 free (3 total)
				$total_items_received = $quantity * ( 1 + $free_per_paid );

				// Determine base price: use current price (which may be WC sale price)
				// unless apply_on_sale_price is off and item is on sale
				$final_price = $current_price;

				// Check if stacking is allowed - if so, look for other discounts
				if ( $allow_stacking ) {
					$other_discount = self::find_best_discount( $actual_product_id, $active_events, $original_price );
					if ( $other_discount ) {
						// Apply the additional discount to the BOGO item
						$final_price = self::calculate_discounted_price( $original_price, $other_discount );
						$product->set_price( $final_price );
					}
				}

				// Store BOGO info for display - MUST update cart contents directly, not the loop copy
				WC()->cart->cart_contents[ $cart_item_key ]['ghsales_bogo_display'] = array(
					'original_price' => $original_price,
					'final_price' => $final_price,
					'total_items' => $total_items_received,
					'free_per_paid' => $free_per_paid,
					'event_name' => $bogo_data['event_name'],
					'has_additional_discount' => $final_price < $original_price,
				);

				error_log( 'GHSales: Set BOGO display data for cart item ' . $cart_item_key . ' - qty=' . $quantity . ', total=' . $total_items_received );

				continue; // Skip normal discount checks for BOGO items
			}

			// Find applicable discount for non-BOGO products
			$discount = self::find_best_discount( $actual_product_id, $active_events, $original_price );

			if ( $discount ) {
				// Check purchase limit if set for this discount rule
				if ( ! empty( $discount['max_quantity'] ) ) {
					$limit_check = self::check_purchase_limit(
						$discount['rule_id'],
						$discount['max_quantity'],
						$quantity
					);

					// If limit exceeded, auto-reduce quantity to maximum allowed
					if ( ! $limit_check['allowed'] ) {
						$max_allowed = $limit_check['remaining'] + $limit_check['purchased'];
						error_log( 'GHSales: Regular discount limit reached (qty=' . $quantity . ', limit=' . $discount['max_quantity'] . ') - auto-reducing to ' . $max_allowed );

						// Set quantity to maximum allowed
						WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $max_allowed;
						$quantity = $max_allowed; // Update local variable

						// Show warning that we reduced the quantity
						WC()->cart->cart_contents[ $cart_item_key ]['ghsales_limit_reached'] = array(
							'remaining' => 0, // No more remaining after this
							'max_quantity' => $discount['max_quantity'],
						);

						// If max_allowed is 0, skip applying discount
						if ( $max_allowed === 0 ) {
							continue;
						}
					}
				}

				// Apply the discount
				$new_price = self::calculate_discounted_price( $original_price, $discount );
				$product->set_price( $new_price );

				// Store discount info in cart item for display - MUST update cart contents directly
				WC()->cart->cart_contents[ $cart_item_key ]['ghsales_discount'] = array(
					'original_price' => $original_price,
					'discounted_price' => $new_price,
					'discount_type' => $discount['type'],
					'discount_value' => $discount['value'],
					'event_name' => $discount['event_name'],
					'rule_id' => $discount['rule_id'],
					'max_quantity' => $discount['max_quantity'],
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
						'rule_id' => intval( $rule->id ),
						'max_quantity' => ! empty( $rule->max_quantity_per_customer ) ? intval( $rule->max_quantity_per_customer ) : null,
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
		error_log( 'GHSales: handle_bogo_addition called - product_id=' . $product_id . ', qty=' . $quantity . ', cart_key=' . $cart_item_key );

		// Get active events
		$active_events = self::get_active_events();

		if ( empty( $active_events ) ) {
			error_log( 'GHSales: No active events found' );
			return;
		}

		$actual_product_id = $variation_id ? $variation_id : $product_id;
		error_log( 'GHSales: Looking for BOGO rules for product_id=' . $actual_product_id );

		// Find BOGO rules that apply to this product
		$bogo_rule = self::find_bogo_rule( $actual_product_id, $active_events );
		error_log( 'GHSales: BOGO rule found: ' . ( $bogo_rule ? 'YES' : 'NO' ) );

		if ( $bogo_rule ) {
			error_log( 'GHSales: Applying BOGO rule - free_items=' . $bogo_rule['free_items'] );

			// Check purchase limit if set
			if ( ! empty( $bogo_rule['max_quantity'] ) ) {
				$limit_check = self::check_purchase_limit(
					$bogo_rule['rule_id'],
					$bogo_rule['max_quantity'],
					$quantity
				);

				// If limit exceeded, auto-reduce quantity to maximum allowed
				if ( ! $limit_check['allowed'] ) {
					$max_allowed = $limit_check['remaining'] + $limit_check['purchased'];
					error_log( 'GHSales: Purchase limit reached (qty=' . $quantity . ') - auto-reducing to ' . $max_allowed );

					// Set quantity to maximum allowed
					WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = $max_allowed;
					$quantity = $max_allowed; // Update local variable for BOGO calculation

					// Store limit info for displaying message
					WC()->cart->cart_contents[ $cart_item_key ]['ghsales_limit_reached'] = array(
						'remaining' => 0, // No more remaining after this
						'max_quantity' => $bogo_rule['max_quantity'],
					);

					// Continue to apply BOGO on the reduced quantity
				}
			}

			// Mark this cart item as BOGO (we'll handle pricing in apply_cart_discounts)
			WC()->cart->cart_contents[ $cart_item_key ]['ghsales_bogo'] = array(
				'rule_id' => intval( $bogo_rule['rule_id'] ),
				'free_per_paid' => intval( $bogo_rule['free_items'] ),
				'event_name' => $bogo_rule['event_name'],
				'max_quantity' => $bogo_rule['max_quantity'],
				'allow_stacking' => ! empty( $bogo_rule['allow_stacking'] ),
				'apply_on_sale_price' => ! empty( $bogo_rule['apply_on_sale_price'] ),
			);

			error_log( 'GHSales: Successfully set ghsales_bogo for cart item ' . $cart_item_key );
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
						'rule_id' => intval( $rule->id ),
						'free_items' => intval( $rule->discount_value ),
						'event_name' => $event->post_title,
						'allow_stacking' => ! empty( $event->allow_stacking ),
						'apply_on_sale_price' => ! empty( $event->apply_on_sale_price ),
						'max_quantity' => ! empty( $rule->max_quantity_per_customer ) ? intval( $rule->max_quantity_per_customer ) : null,
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

			// Check if there's also a stacked discount
			if ( ! empty( $bogo['has_additional_discount'] ) ) {
				// Show original price strikethrough, then discounted price with BOGO label
				return sprintf(
					'<del>%s</del> <ins>%s</ins><br><small class="ghsales-bogo-label" style="color: #46b450; font-weight: bold;">%s</small>',
					wc_price( $bogo['original_price'] ),
					wc_price( $bogo['final_price'] ),
					sprintf( __( '1+%d FREE + Sale', 'ghsales' ), $free_per_paid )
				);
			} else {
				// Just BOGO, no additional discount
				return sprintf(
					'%s<br><small class="ghsales-bogo-label" style="color: #46b450; font-weight: bold;">%s</small>',
					wc_price( $bogo['original_price'] ),
					sprintf( __( '1+%d FREE', 'ghsales' ), $free_per_paid )
				);
			}
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

		// First check for BOGO
		$bogo_rule = self::find_bogo_rule( $product_id, $active_events );
		if ( $bogo_rule ) {
			// Get translated BOGO badge text
			$badge_text = sprintf(
				GHSales_i18n::get( 'bogo_badge', '1+%d FREE' ),
				1,
				intval( $bogo_rule['free_items'] )
			);
			return '<span class="onsale ghsales-badge ghsales-bogo-product-badge">' . esc_html( $badge_text ) . '</span>';
		}

		// Check for regular discount
		$discount = self::find_best_discount( $product_id, $active_events, $product->get_price() );
		if ( $discount ) {
			// Get the event post to retrieve badge display setting
			$badge_display = 'percentage'; // Default
			foreach ( $active_events as $event ) {
				if ( $event->post_title === $discount['event_name'] ) {
					$badge_display = get_post_meta( $event->ID, '_ghsales_badge_display', true );
					if ( empty( $badge_display ) ) {
						$badge_display = 'percentage';
					}
					break;
				}
			}

			// Calculate original and discounted prices
			$original_price = floatval( $product->get_regular_price() );
			$current_price = floatval( $product->get_price() );

			// If product already has WC sale, use that as original
			if ( $product->is_on_sale() && $original_price > $current_price ) {
				$original_price = $current_price;
			}

			$discounted_price = self::calculate_discounted_price( $original_price, $discount );
			$amount_saved = $original_price - $discounted_price;
			$percentage_saved = $original_price > 0 ? round( ( $amount_saved / $original_price ) * 100 ) : 0;

			// Build badge text based on setting
			$badge_text = '';
			if ( $badge_display === 'percentage' ) {
				$badge_text = '-' . $percentage_saved . '%';
			} elseif ( $badge_display === 'amount' ) {
				$badge_text = '-' . strip_tags( wc_price( $amount_saved ) );
			} elseif ( $badge_display === 'both' ) {
				$badge_text = '-' . $percentage_saved . '% / -' . strip_tags( wc_price( $amount_saved ) );
			}

			return '<span class="onsale ghsales-badge ghsales-discount-product-badge">' . esc_html( $badge_text ) . '</span>';
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
		// Debug: Log if BOGO display data exists
		error_log( 'GHSales add_bogo_info_to_name called for: ' . $name );
		error_log( 'Has ghsales_bogo_display: ' . ( isset( $cart_item['ghsales_bogo_display'] ) ? 'YES' : 'NO' ) );

		if ( isset( $cart_item['ghsales_bogo_display'] ) ) {
			$bogo = $cart_item['ghsales_bogo_display'];
			$free_per_paid = $bogo['free_per_paid'];
			$quantity = $cart_item['quantity'];
			$total_received = $bogo['total_items'];

			error_log( 'GHSales: Adding BOGO badge - free_per_paid=' . $free_per_paid . ', qty=' . $quantity . ', total=' . $total_received );

			// Get translated badge text (e.g., "1+1 GRATIS" in Dutch, "1+1 FREE" in English)
			$badge_text = sprintf(
				GHSales_i18n::get( 'bogo_badge', '1+%d FREE' ),
				1,
				$free_per_paid
			);

			// Add BOGO badge AND quantity data attribute for JavaScript
			$name .= sprintf(
				' <span class="ghsales-bogo-badge" data-free-per-paid="%d" data-paid-qty="%d" data-total-qty="%d" style="align-self: flex-start; background: #000; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">%s</span>',
				$free_per_paid,
				$quantity,
				$total_received,
				esc_html( $badge_text )
			);
		}

		return $name;
	}

	/**
	 * Add discount badge to product name in cart for regular (non-BOGO) discounts
	 *
	 * @param string $name Product name HTML
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 * @return string Modified name HTML
	 */
	public static function add_discount_badge_to_name( $name, $cart_item, $cart_item_key ) {
		// Only show badge for regular discounts (not BOGO)
		if ( isset( $cart_item['ghsales_discount'] ) && ! isset( $cart_item['ghsales_bogo_display'] ) ) {
			$discount = $cart_item['ghsales_discount'];
			$original_price = $discount['original_price'];
			$discounted_price = $discount['discounted_price'];
			$event_name = $discount['event_name'];

			// Get the event post to retrieve badge display setting
			$events = self::get_active_events();
			$badge_display = 'percentage'; // Default

			foreach ( $events as $event ) {
				if ( $event->post_title === $event_name ) {
					$badge_display = get_post_meta( $event->ID, '_ghsales_badge_display', true );
					if ( empty( $badge_display ) ) {
						$badge_display = 'percentage';
					}
					break;
				}
			}

			// Calculate savings
			$amount_saved = $original_price - $discounted_price;
			$percentage_saved = $original_price > 0 ? round( ( $amount_saved / $original_price ) * 100 ) : 0;

			// Build badge text based on setting
			$badge_text = '';
			if ( $badge_display === 'percentage' ) {
				$badge_text = '-' . $percentage_saved . '%';
			} elseif ( $badge_display === 'amount' ) {
				$badge_text = '-' . wc_price( $amount_saved );
			} elseif ( $badge_display === 'both' ) {
				$badge_text = '-' . $percentage_saved . '% / -' . wc_price( $amount_saved );
			}

			// Add discount badge
			$name .= sprintf(
				' <span class="ghsales-discount-badge" style="align-self: flex-start; background: #000; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">%s</span>',
				$badge_text
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

	/**
	 * Display limit reached message for products
	 *
	 * @param string $name Product name
	 * @param array  $cart_item Cart item data
	 * @param string $cart_item_key Cart item key
	 * @return string Modified product name with limit message
	 */
	public static function display_limit_message( $name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['ghsales_limit_reached'] ) ) {
			$limit_info = $cart_item['ghsales_limit_reached'];
			$remaining = $limit_info['remaining'];
			$max = $limit_info['max_quantity'];

			if ( $remaining === 0 ) {
				$message = sprintf(
					GHSales_i18n::get( 'sale_limit_reached', 'Sale limit reached: Maximum %d items per customer' ),
					$max
				);
				$name .= sprintf(
					'<br><small class="ghsales-limit-message" style="color: #dc3232; font-weight: bold;">%s</small>',
					esc_html( $message )
				);
			} else {
				$message = sprintf(
					GHSales_i18n::get( 'sale_limit_remaining', 'Sale limit: %d of %d remaining' ),
					$remaining,
					$max
				);
				$name .= sprintf(
					'<br><small class="ghsales-limit-message" style="color: #f0b849; font-weight: bold;">%s</small>',
					esc_html( $message )
				);
			}
		}

		return $name;
	}

	/**
	 * Save BOGO data to order item meta for tracking
	 *
	 * @param WC_Order_Item_Product $item Order item
	 * @param string $cart_item_key Cart item key
	 * @param array $values Cart item values
	 * @param WC_Order $order Order object
	 * @return void
	 */
	public static function save_bogo_to_order_item( $item, $cart_item_key, $values, $order ) {
		// Save BOGO data if present
		if ( isset( $values['ghsales_bogo'] ) ) {
			$item->add_meta_data( '_ghsales_bogo', $values['ghsales_bogo'], true );
		}

		// Save BOGO display data for showing in order details
		if ( isset( $values['ghsales_bogo_display'] ) ) {
			$item->add_meta_data( '_ghsales_bogo_display', $values['ghsales_bogo_display'], true );
		}

		// Save regular discount data if present (for purchase limit tracking)
		if ( isset( $values['ghsales_discount'] ) ) {
			$item->add_meta_data( '_ghsales_discount', $values['ghsales_discount'], true );
		}
	}

	/**
	 * Track purchases from completed order for limit enforcement
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public static function track_order_purchases( $order_id ) {
		// Get order
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if we already tracked this order
		$tracked = $order->get_meta( '_ghsales_limits_tracked', true );
		if ( $tracked ) {
			return; // Already tracked
		}

		// Loop through order items
		foreach ( $order->get_items() as $item ) {
			// Check if this item had BOGO applied
			$bogo_data = $item->get_meta( '_ghsales_bogo', true );
			if ( $bogo_data && isset( $bogo_data['rule_id'] ) ) {
				// Track this purchase
				self::track_purchase(
					intval( $bogo_data['rule_id'] ),
					intval( $item->get_quantity() )
				);
			}

			// Check if this item had regular discount applied
			$discount_data = $item->get_meta( '_ghsales_discount', true );
			if ( $discount_data && isset( $discount_data['rule_id'] ) && ! empty( $discount_data['max_quantity'] ) ) {
				// Track this purchase
				self::track_purchase(
					intval( $discount_data['rule_id'] ),
					intval( $item->get_quantity() )
				);
			}
		}

		// Mark order as tracked
		$order->update_meta_data( '_ghsales_limits_tracked', true );
		$order->save();
	}

	/**
	 * Get customer identifier for purchase limit tracking
	 * Uses email for logged-in users, session ID for guests
	 *
	 * @return string Customer identifier
	 */
	private static function get_customer_identifier() {
		// For logged-in users, use email address
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return 'email_' . $user->user_email;
		}

		// For guests, use WooCommerce session ID
		if ( WC()->session ) {
			$session_id = WC()->session->get_customer_id();
			if ( $session_id ) {
				return 'session_' . $session_id;
			}
		}

		// Fallback to IP address (less reliable but better than nothing)
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		return 'ip_' . $ip;
	}

	/**
	 * Check if customer has reached purchase limit for a rule
	 *
	 * @param int $rule_id Rule ID
	 * @param int $max_quantity Maximum quantity allowed
	 * @param int $requested_quantity Quantity customer wants to add
	 * @return array ['allowed' => bool, 'remaining' => int, 'purchased' => int]
	 */
	public static function check_purchase_limit( $rule_id, $max_quantity, $requested_quantity ) {
		global $wpdb;

		// If no limit set, allow unlimited
		if ( empty( $max_quantity ) || $max_quantity <= 0 ) {
			return array(
				'allowed' => true,
				'remaining' => PHP_INT_MAX,
				'purchased' => 0,
			);
		}

		$customer_id = self::get_customer_identifier();

		// Get current purchased quantity
		$purchased = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT quantity_purchased FROM {$wpdb->prefix}ghsales_purchase_limits
				WHERE rule_id = %d AND customer_identifier = %s",
				$rule_id,
				$customer_id
			)
		);

		$purchased = $purchased ? intval( $purchased ) : 0;
		$remaining = max( 0, $max_quantity - $purchased );
		$allowed = $requested_quantity <= $remaining;

		return array(
			'allowed' => $allowed,
			'remaining' => $remaining,
			'purchased' => $purchased,
		);
	}

	/**
	 * Track purchase for limit enforcement
	 * Called after successful order completion
	 *
	 * @param int $rule_id Rule ID
	 * @param int $quantity Quantity purchased
	 * @return void
	 */
	public static function track_purchase( $rule_id, $quantity ) {
		global $wpdb;

		$customer_id = self::get_customer_identifier();

		// Check if record exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT quantity_purchased FROM {$wpdb->prefix}ghsales_purchase_limits
				WHERE rule_id = %d AND customer_identifier = %s",
				$rule_id,
				$customer_id
			)
		);

		if ( $existing !== null ) {
			// Update existing record
			$wpdb->update(
				$wpdb->prefix . 'ghsales_purchase_limits',
				array(
					'quantity_purchased' => intval( $existing ) + intval( $quantity ),
					'last_updated' => current_time( 'mysql' ),
				),
				array(
					'rule_id' => $rule_id,
					'customer_identifier' => $customer_id,
				),
				array( '%d', '%s' ),
				array( '%d', '%s' )
			);
		} else {
			// Insert new record
			$wpdb->insert(
				$wpdb->prefix . 'ghsales_purchase_limits',
				array(
					'rule_id' => $rule_id,
					'customer_identifier' => $customer_id,
					'quantity_purchased' => intval( $quantity ),
					'last_updated' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s' )
			);
		}
	}
}
