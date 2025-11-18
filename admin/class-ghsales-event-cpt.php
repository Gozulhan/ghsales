<?php
/**
 * GHSales Event Custom Post Type
 *
 * Registers and manages the Sale Events custom post type.
 * Provides admin interface for creating and managing sale events.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Event_CPT Class
 *
 * Manages Sale Events custom post type
 */
class GHSales_Event_CPT {

	/**
	 * Initialize custom post type
	 * Registers post type and meta boxes
	 *
	 * @return void
	 */
	public static function init() {
		// Register custom post type
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );

		// Add meta boxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );

		// Save meta data
		add_action( 'save_post_ghsales_event', array( __CLASS__, 'save_meta_data' ), 10, 2 );

		// Customize admin columns
		add_filter( 'manage_ghsales_event_posts_columns', array( __CLASS__, 'customize_columns' ) );
		add_action( 'manage_ghsales_event_posts_custom_column', array( __CLASS__, 'render_column_content' ), 10, 2 );

		// Add custom row actions
		add_filter( 'post_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );

		// AJAX handler for loading target selectors
		add_action( 'wp_ajax_ghsales_load_target_selector', array( __CLASS__, 'ajax_load_target_selector' ) );
	}

	/**
	 * Register Sale Event custom post type
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Sale Events', 'ghsales' ),
			'singular_name'         => __( 'Sale Event', 'ghsales' ),
			'menu_name'             => __( 'Sale Events', 'ghsales' ),
			'add_new'               => __( 'Add New', 'ghsales' ),
			'add_new_item'          => __( 'Add New Sale Event', 'ghsales' ),
			'edit_item'             => __( 'Edit Sale Event', 'ghsales' ),
			'new_item'              => __( 'New Sale Event', 'ghsales' ),
			'view_item'             => __( 'View Sale Event', 'ghsales' ),
			'search_items'          => __( 'Search Sale Events', 'ghsales' ),
			'not_found'             => __( 'No sale events found', 'ghsales' ),
			'not_found_in_trash'    => __( 'No sale events found in trash', 'ghsales' ),
			'all_items'             => __( 'All Sale Events', 'ghsales' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => 'ghsales',
			'menu_position'       => 56,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'author' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => true,
			'show_in_rest'        => false,
		);

		register_post_type( 'ghsales_event', $args );
	}

	/**
	 * Add meta boxes to event editor
	 *
	 * @return void
	 */
	public static function add_meta_boxes() {
		// Basic Info meta box
		add_meta_box(
			'ghsales_event_basic_info',
			__( 'Event Details', 'ghsales' ),
			array( __CLASS__, 'render_basic_info_meta_box' ),
			'ghsales_event',
			'normal',
			'high'
		);

		// Sale Rules meta box
		add_meta_box(
			'ghsales_event_rules',
			__( 'Sale Rules', 'ghsales' ),
			array( __CLASS__, 'render_rules_meta_box' ),
			'ghsales_event',
			'normal',
			'high'
		);

		// Event Settings meta box
		add_meta_box(
			'ghsales_event_settings',
			__( 'Event Settings', 'ghsales' ),
			array( __CLASS__, 'render_settings_meta_box' ),
			'ghsales_event',
			'side',
			'default'
		);
	}

	/**
	 * Render Basic Info meta box
	 *
	 * @param WP_Post $post Post object
	 * @return void
	 */
	public static function render_basic_info_meta_box( $post ) {
		// Get saved values
		$start_date = get_post_meta( $post->ID, '_ghsales_start_date', true );
		$end_date   = get_post_meta( $post->ID, '_ghsales_end_date', true );
		$description = get_post_meta( $post->ID, '_ghsales_description', true );

		// Nonce for security
		wp_nonce_field( 'ghsales_event_meta', 'ghsales_event_meta_nonce' );

		?>
		<table class="form-table">
			<tr>
				<th><label for="ghsales_start_date"><?php esc_html_e( 'Start Date & Time', 'ghsales' ); ?></label></th>
				<td>
					<input type="datetime-local"
						   id="ghsales_start_date"
						   name="ghsales_start_date"
						   value="<?php echo esc_attr( $start_date ? date( 'Y-m-d\TH:i', strtotime( $start_date ) ) : '' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'When this sale event becomes active', 'ghsales' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="ghsales_end_date"><?php esc_html_e( 'End Date & Time', 'ghsales' ); ?></label></th>
				<td>
					<input type="datetime-local"
						   id="ghsales_end_date"
						   name="ghsales_end_date"
						   value="<?php echo esc_attr( $end_date ? date( 'Y-m-d\TH:i', strtotime( $end_date ) ) : '' ); ?>"
						   class="regular-text">
					<p class="description"><?php esc_html_e( 'When this sale event ends', 'ghsales' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="ghsales_description"><?php esc_html_e( 'Description', 'ghsales' ); ?></label></th>
				<td>
					<textarea id="ghsales_description"
							  name="ghsales_description"
							  rows="4"
							  class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Internal description for reference (not shown to customers)', 'ghsales' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Sale Rules meta box
	 *
	 * @param WP_Post $post Post object
	 * @return void
	 */
	public static function render_rules_meta_box( $post ) {
		global $wpdb;

		// Get existing rules from database
		$rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ghsales_rules WHERE event_id = %d ORDER BY priority DESC",
				$post->ID
			)
		);

		?>
		<div id="ghsales-rules-container">
			<?php if ( ! empty( $rules ) ) : ?>
				<?php foreach ( $rules as $index => $rule ) : ?>
					<?php self::render_rule_row( $rule, $index ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No sale rules added yet. Click "Add Rule" to create your first discount rule.', 'ghsales' ); ?></p>
			<?php endif; ?>
		</div>

		<p>
			<button type="button" class="button button-secondary" id="ghsales-add-rule">
				<?php esc_html_e( '+ Add Sale Rule', 'ghsales' ); ?>
			</button>
		</p>

		<!-- Template for new rules (hidden) -->
		<script type="text/template" id="ghsales-rule-template">
			<?php self::render_rule_row( null, '{{INDEX}}' ); ?>
		</script>

		<style>
			.ghsales-rule-row {
				background: #f9f9f9;
				border: 1px solid #ddd;
				padding: 15px;
				margin-bottom: 15px;
				border-radius: 4px;
			}
			.ghsales-rule-row h4 {
				margin-top: 0;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.ghsales-rule-field {
				margin-bottom: 15px;
			}
			.ghsales-rule-field label {
				display: block;
				font-weight: bold;
				margin-bottom: 5px;
			}
			.ghsales-remove-rule {
				color: #a00;
				text-decoration: none;
			}
			.ghsales-remove-rule:hover {
				color: #dc3232;
			}
		</style>
		<?php
	}

	/**
	 * Render individual rule row
	 *
	 * @param object|null $rule Rule data or null for template
	 * @param int|string  $index Rule index
	 * @return void
	 */
	private static function render_rule_row( $rule, $index ) {
		$rule_id       = $rule ? $rule->id : '';
		$rule_type     = $rule ? $rule->rule_type : 'percentage';
		$applies_to    = $rule ? $rule->applies_to : 'all';
		$target_ids    = $rule ? $rule->target_ids : '';
		$discount_value = $rule ? $rule->discount_value : '';
		$priority      = $rule ? $rule->priority : 0;
		$max_quantity  = $rule ? $rule->max_quantity_per_customer : '';

		// Calculate display number (handle both numeric and template placeholder)
		$display_number = is_numeric( $index ) ? ( $index + 1 ) : '{{DISPLAY_NUMBER}}';

		?>
		<div class="ghsales-rule-row" data-index="<?php echo esc_attr( $index ); ?>">
			<h4>
				<span><?php printf( esc_html__( 'Rule #%s', 'ghsales' ), esc_html( $display_number ) ); ?></span>
				<a href="#" class="ghsales-remove-rule" data-index="<?php echo esc_attr( $index ); ?>">
					<?php esc_html_e( 'Remove', 'ghsales' ); ?>
				</a>
			</h4>

			<input type="hidden" name="ghsales_rules[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $rule_id ); ?>">

			<div class="ghsales-rule-field">
				<label><?php esc_html_e( 'Discount Type', 'ghsales' ); ?></label>
				<select name="ghsales_rules[<?php echo esc_attr( $index ); ?>][rule_type]" class="widefat">
					<option value="percentage" <?php selected( $rule_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Off', 'ghsales' ); ?></option>
					<option value="fixed" <?php selected( $rule_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount Off', 'ghsales' ); ?></option>
					<option value="bogo" <?php selected( $rule_type, 'bogo' ); ?>><?php esc_html_e( 'Buy One Get One (BOGO)', 'ghsales' ); ?></option>
					<option value="buy_x_get_y" <?php selected( $rule_type, 'buy_x_get_y' ); ?>><?php esc_html_e( 'Buy X Get Y', 'ghsales' ); ?></option>
					<option value="spend_threshold" <?php selected( $rule_type, 'spend_threshold' ); ?>><?php esc_html_e( 'Spend Threshold Discount', 'ghsales' ); ?></option>
				</select>
			</div>

			<div class="ghsales-rule-field">
				<label><?php esc_html_e( 'Applies To', 'ghsales' ); ?></label>
				<select name="ghsales_rules[<?php echo esc_attr( $index ); ?>][applies_to]" class="widefat ghsales-applies-to" data-index="<?php echo esc_attr( $index ); ?>">
					<option value="all" <?php selected( $applies_to, 'all' ); ?>><?php esc_html_e( 'All Products', 'ghsales' ); ?></option>
					<option value="products" <?php selected( $applies_to, 'products' ); ?>><?php esc_html_e( 'Specific Products', 'ghsales' ); ?></option>
					<option value="categories" <?php selected( $applies_to, 'categories' ); ?>><?php esc_html_e( 'Product Categories', 'ghsales' ); ?></option>
					<option value="tags" <?php selected( $applies_to, 'tags' ); ?>><?php esc_html_e( 'Product Tags', 'ghsales' ); ?></option>
				</select>
			</div>

			<div class="ghsales-rule-field ghsales-target-selector" data-index="<?php echo esc_attr( $index ); ?>" style="<?php echo ( $applies_to === 'all' ) ? 'display:none;' : ''; ?>">
				<label><?php esc_html_e( 'Select Target', 'ghsales' ); ?></label>
				<div class="ghsales-target-content">
					<?php self::render_target_selector( $applies_to, $target_ids, $index ); ?>
				</div>
			</div>

			<div class="ghsales-rule-field">
				<label class="ghsales-discount-value-label" data-index="<?php echo esc_attr( $index ); ?>">
					<?php esc_html_e( 'Discount Value', 'ghsales' ); ?>
				</label>
				<input type="number"
					   name="ghsales_rules[<?php echo esc_attr( $index ); ?>][discount_value]"
					   value="<?php echo esc_attr( $discount_value ); ?>"
					   step="0.01"
					   min="0"
					   class="widefat ghsales-discount-value-input"
					   data-index="<?php echo esc_attr( $index ); ?>"
					   placeholder="<?php esc_attr_e( 'e.g., 10 for 10% off or €10 fixed', 'ghsales' ); ?>">
				<p class="description ghsales-discount-value-help" data-index="<?php echo esc_attr( $index ); ?>"></p>
			</div>

			<div class="ghsales-rule-field">
				<label><?php esc_html_e( 'Priority', 'ghsales' ); ?></label>
				<input type="number"
					   name="ghsales_rules[<?php echo esc_attr( $index ); ?>][priority]"
					   value="<?php echo esc_attr( $priority ); ?>"
					   min="0"
					   class="small-text">
				<p class="description"><?php esc_html_e( 'Higher number = higher priority. Used when multiple rules match the same product.', 'ghsales' ); ?></p>
			</div>

			<div class="ghsales-rule-field">
				<label><?php esc_html_e( 'Max Quantity Per Customer (Optional)', 'ghsales' ); ?></label>
				<input type="number"
					   name="ghsales_rules[<?php echo esc_attr( $index ); ?>][max_quantity_per_customer]"
					   value="<?php echo esc_attr( $max_quantity ); ?>"
					   min="0"
					   class="small-text"
					   placeholder="<?php esc_attr_e( 'Unlimited', 'ghsales' ); ?>">
				<p class="description"><?php esc_html_e( 'Maximum quantity a customer can purchase with this discount. Leave empty for unlimited. (Tracked per email for logged-in users, per session for guests)', 'ghsales' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render target selector (products, categories, or tags)
	 *
	 * @param string     $applies_to Type: products, categories, or tags
	 * @param string     $target_ids Comma-separated IDs
	 * @param int|string $index Rule index
	 * @return void
	 */
	private static function render_target_selector( $applies_to, $target_ids, $index ) {
		$selected_ids = ! empty( $target_ids ) ? array_map( 'trim', explode( ',', $target_ids ) ) : array();

		if ( $applies_to === 'products' ) {
			// Get all products
			$products = wc_get_products( array(
				'limit'  => -1,
				'status' => 'publish',
				'orderby' => 'title',
				'order'   => 'ASC',
			) );

			?>
			<select name="ghsales_rules[<?php echo esc_attr( $index ); ?>][target_ids][]"
					class="ghsales-select2"
					multiple="multiple"
					style="width: 100%;"
					data-placeholder="<?php esc_attr_e( 'Search and select products...', 'ghsales' ); ?>">
				<?php foreach ( $products as $product ) : ?>
					<option value="<?php echo esc_attr( $product->get_id() ); ?>"
							<?php echo in_array( $product->get_id(), $selected_ids ) ? 'selected' : ''; ?>>
						<?php echo esc_html( $product->get_name() . ' (#' . $product->get_id() . ')' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php

		} elseif ( $applies_to === 'categories' ) {
			// Get all product categories
			$categories = get_terms( array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );

			?>
			<select name="ghsales_rules[<?php echo esc_attr( $index ); ?>][target_ids][]"
					class="ghsales-select2"
					multiple="multiple"
					style="width: 100%;"
					data-placeholder="<?php esc_attr_e( 'Search and select categories...', 'ghsales' ); ?>">
				<?php foreach ( $categories as $category ) : ?>
					<option value="<?php echo esc_attr( $category->term_id ); ?>"
							<?php echo in_array( $category->term_id, $selected_ids ) ? 'selected' : ''; ?>>
						<?php echo esc_html( $category->name . ' (' . $category->count . ' products)' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php

		} elseif ( $applies_to === 'tags' ) {
			// Get all product tags
			$tags = get_terms( array(
				'taxonomy'   => 'product_tag',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			) );

			?>
			<select name="ghsales_rules[<?php echo esc_attr( $index ); ?>][target_ids][]"
					class="ghsales-select2"
					multiple="multiple"
					style="width: 100%;"
					data-placeholder="<?php esc_attr_e( 'Search and select tags...', 'ghsales' ); ?>">
				<?php foreach ( $tags as $tag ) : ?>
					<option value="<?php echo esc_attr( $tag->term_id ); ?>"
							<?php echo in_array( $tag->term_id, $selected_ids ) ? 'selected' : ''; ?>>
						<?php echo esc_html( $tag->name . ' (' . $tag->count . ' products)' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Render Settings meta box (sidebar)
	 *
	 * @param WP_Post $post Post object
	 * @return void
	 */
	public static function render_settings_meta_box( $post ) {
		$allow_stacking    = get_post_meta( $post->ID, '_ghsales_allow_stacking', true );
		$apply_on_sale     = get_post_meta( $post->ID, '_ghsales_apply_on_sale_price', true );
		$color_scheme_id   = get_post_meta( $post->ID, '_ghsales_color_scheme_id', true );

		// Get color schemes
		global $wpdb;
		$color_schemes = $wpdb->get_results(
			"SELECT id, scheme_name FROM {$wpdb->prefix}ghsales_color_schemes ORDER BY scheme_name"
		);

		?>
		<p>
			<label>
				<input type="checkbox"
					   name="ghsales_allow_stacking"
					   value="1"
					   <?php checked( $allow_stacking, '1' ); ?>>
				<?php esc_html_e( 'Allow stacking with other events', 'ghsales' ); ?>
			</label>
		</p>

		<p>
			<label>
				<input type="checkbox"
					   name="ghsales_apply_on_sale_price"
					   value="1"
					   <?php checked( $apply_on_sale, '1' ); ?>>
				<?php esc_html_e( 'Apply on WooCommerce sale price', 'ghsales' ); ?>
			</label>
		</p>

		<p>
			<label for="ghsales_color_scheme"><?php esc_html_e( 'Color Scheme (optional)', 'ghsales' ); ?></label>
			<select id="ghsales_color_scheme" name="ghsales_color_scheme_id" class="widefat">
				<option value=""><?php esc_html_e( '-- None --', 'ghsales' ); ?></option>
				<?php foreach ( $color_schemes as $scheme ) : ?>
					<option value="<?php echo esc_attr( $scheme->id ); ?>" <?php selected( $color_scheme_id, $scheme->id ); ?>>
						<?php echo esc_html( $scheme->scheme_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save meta data when event is saved
	 *
	 * @param int     $post_id Post ID
	 * @param WP_Post $post Post object
	 * @return void
	 */
	public static function save_meta_data( $post_id, $post ) {
		// Check nonce
		if ( ! isset( $_POST['ghsales_event_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ghsales_event_meta_nonce'], 'ghsales_event_meta' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Save basic info
		if ( isset( $_POST['ghsales_start_date'] ) ) {
			update_post_meta( $post_id, '_ghsales_start_date', sanitize_text_field( $_POST['ghsales_start_date'] ) );
		}

		if ( isset( $_POST['ghsales_end_date'] ) ) {
			update_post_meta( $post_id, '_ghsales_end_date', sanitize_text_field( $_POST['ghsales_end_date'] ) );
		}

		if ( isset( $_POST['ghsales_description'] ) ) {
			update_post_meta( $post_id, '_ghsales_description', sanitize_textarea_field( $_POST['ghsales_description'] ) );
		}

		// Save settings
		update_post_meta( $post_id, '_ghsales_allow_stacking', isset( $_POST['ghsales_allow_stacking'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_ghsales_apply_on_sale_price', isset( $_POST['ghsales_apply_on_sale_price'] ) ? '1' : '0' );

		if ( isset( $_POST['ghsales_color_scheme_id'] ) ) {
			update_post_meta( $post_id, '_ghsales_color_scheme_id', absint( $_POST['ghsales_color_scheme_id'] ) );
		}

		// Save rules to database
		self::save_rules( $post_id );
	}

	/**
	 * Save sale rules to database
	 *
	 * @param int $event_id Event post ID
	 * @return void
	 */
	private static function save_rules( $event_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ghsales_rules';

		// Delete existing rules for this event
		$wpdb->delete( $table, array( 'event_id' => $event_id ), array( '%d' ) );

		// Save new rules
		if ( ! empty( $_POST['ghsales_rules'] ) && is_array( $_POST['ghsales_rules'] ) ) {
			foreach ( $_POST['ghsales_rules'] as $rule_data ) {
				// Handle target_ids (can be array from Select2 or string)
				$target_ids = '';
				if ( isset( $rule_data['target_ids'] ) ) {
					if ( is_array( $rule_data['target_ids'] ) ) {
						$target_ids = implode( ',', array_map( 'absint', $rule_data['target_ids'] ) );
					} else {
						$target_ids = sanitize_text_field( $rule_data['target_ids'] );
					}
				}

				// Get max quantity (can be empty for unlimited)
				$max_quantity = null;
				if ( isset( $rule_data['max_quantity_per_customer'] ) && $rule_data['max_quantity_per_customer'] !== '' ) {
					$max_quantity = absint( $rule_data['max_quantity_per_customer'] );
					// Convert 0 to null (means unlimited)
					if ( $max_quantity === 0 ) {
						$max_quantity = null;
					}
				}

				$wpdb->insert(
					$table,
					array(
						'event_id'                  => $event_id,
						'rule_type'                 => sanitize_text_field( $rule_data['rule_type'] ),
						'applies_to'                => sanitize_text_field( $rule_data['applies_to'] ),
						'target_ids'                => $target_ids,
						'discount_value'            => floatval( $rule_data['discount_value'] ),
						'priority'                  => absint( $rule_data['priority'] ),
						'max_quantity_per_customer' => $max_quantity,
					),
					array( '%d', '%s', '%s', '%s', '%f', '%d', '%d' )
				);
			}
		}
	}

	/**
	 * Customize admin columns
	 *
	 * @param array $columns Existing columns
	 * @return array Modified columns
	 */
	public static function customize_columns( $columns ) {
		$new_columns = array(
			'cb'          => $columns['cb'],
			'title'       => $columns['title'],
			'start_date'  => __( 'Start Date', 'ghsales' ),
			'end_date'    => __( 'End Date', 'ghsales' ),
			'status'      => __( 'Status', 'ghsales' ),
			'rules_count' => __( 'Rules', 'ghsales' ),
			'date'        => $columns['date'],
		);

		return $new_columns;
	}

	/**
	 * Render custom column content
	 *
	 * @param string $column Column name
	 * @param int    $post_id Post ID
	 * @return void
	 */
	public static function render_column_content( $column, $post_id ) {
		global $wpdb;

		switch ( $column ) {
			case 'start_date':
				$start_date = get_post_meta( $post_id, '_ghsales_start_date', true );
				echo $start_date ? esc_html( date( 'Y-m-d H:i', strtotime( $start_date ) ) ) : '—';
				break;

			case 'end_date':
				$end_date = get_post_meta( $post_id, '_ghsales_end_date', true );
				echo $end_date ? esc_html( date( 'Y-m-d H:i', strtotime( $end_date ) ) ) : '—';
				break;

			case 'status':
				$start_date = get_post_meta( $post_id, '_ghsales_start_date', true );
				$end_date   = get_post_meta( $post_id, '_ghsales_end_date', true );
				$now        = current_time( 'mysql' );

				if ( ! $start_date || ! $end_date ) {
					echo '<span style="color: #999;">Draft</span>';
				} elseif ( $now < $start_date ) {
					echo '<span style="color: #f0b849;">Scheduled</span>';
				} elseif ( $now >= $start_date && $now <= $end_date ) {
					echo '<span style="color: #46b450;"><strong>Active</strong></span>';
				} else {
					echo '<span style="color: #dc3232;">Ended</span>';
				}
				break;

			case 'rules_count':
				$count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}ghsales_rules WHERE event_id = %d",
						$post_id
					)
				);
				echo absint( $count );
				break;
		}
	}

	/**
	 * Add custom row actions
	 *
	 * @param array   $actions Existing actions
	 * @param WP_Post $post Post object
	 * @return array Modified actions
	 */
	public static function add_row_actions( $actions, $post ) {
		if ( $post->post_type === 'ghsales_event' ) {
			// Add "Duplicate" action (future feature)
			// $actions['duplicate'] = '<a href="#">' . __( 'Duplicate', 'ghsales' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * AJAX handler to load target selector options dynamically
	 * Called when user changes "Applies To" dropdown
	 *
	 * @return void
	 */
	public static function ajax_load_target_selector() {
		// Verify nonce for security
		if ( ! check_ajax_referer( 'ghsales_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh the page.', 'ghsales' ) ) );
		}

		// Get parameters
		$applies_to = isset( $_POST['applies_to'] ) ? sanitize_text_field( $_POST['applies_to'] ) : '';
		$index      = isset( $_POST['index'] ) ? sanitize_text_field( $_POST['index'] ) : null;

		// Validate parameters (note: index can be 0, so check for null/empty string specifically)
		if ( empty( $applies_to ) || ! isset( $_POST['index'] ) || $index === '' ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'ghsales' ) ) );
		}

		// Start output buffering to capture the HTML
		ob_start();
		try {
			self::render_target_selector( $applies_to, '', $index );
			$html = ob_get_clean();

			// Return the HTML
			wp_send_json_success( array( 'html' => $html ) );
		} catch ( Exception $e ) {
			ob_end_clean();
			error_log( 'GHSales AJAX Error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'Error generating selector. Check error log.', 'ghsales' ) ) );
		}
	}
}
