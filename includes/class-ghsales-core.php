<?php
/**
 * GHSales Core Class
 *
 * Main plugin class that initializes all components.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Core Class
 *
 * Central hub for plugin initialization and component management
 */
class GHSales_Core {

	/**
	 * Single instance of the class
	 *
	 * @var GHSales_Core|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 * Ensures only one instance of the plugin core exists
	 *
	 * @return GHSales_Core
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 * Private to enforce singleton pattern
	 * Initializes plugin components and hooks
	 */
	private function __construct() {
		// Load plugin components
		$this->load_components();

		// Initialize plugin components
		$this->init_hooks();
	}

	/**
	 * Load plugin components
	 * Includes and initializes all plugin classes
	 *
	 * @return void
	 */
	private function load_components() {
		// Load i18n (translations)
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-i18n.php';

		// Load tracker
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-tracker.php';
		GHSales_Tracker::init();

		// Load upsell engine
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-upsell.php';
		GHSales_Upsell::init();

		// Load stats aggregator
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-stats.php';
		GHSales_Stats::init();

		// Load Sale Engine (applies discounts to cart)
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-sale-engine.php';
		GHSales_Sale_Engine::init();

		// Load admin components
		if ( is_admin() ) {
			// Sale Event custom post type
			require_once GHSALES_PLUGIN_DIR . 'admin/class-ghsales-event-cpt.php';
			GHSales_Event_CPT::init();
		}
	}
	/**
	 * Initialize WordPress hooks
	 * Sets up actions and filters for plugin functionality
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Admin hooks
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
		}

		// Frontend hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// WooCommerce integration hooks (will be expanded in future phases)
		add_action( 'woocommerce_init', array( $this, 'init_woocommerce_integration' ) );
	}

	/**
	 * Register admin menu pages
	 * Creates admin interface for plugin settings
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		// Main menu page
		add_menu_page(
			__( 'GH Sales', 'ghsales' ),           // Page title
			__( 'GH Sales', 'ghsales' ),           // Menu title
			'manage_woocommerce',                  // Capability (requires WooCommerce manager)
			'ghsales',                             // Menu slug
			array( $this, 'render_admin_page' ),   // Callback
			'dashicons-tag',                       // Icon
			56                                      // Position (after WooCommerce)
		);

		// Submenu pages (will be added in future phases)
		// Sale Events, Color Schemes, Analytics, Settings, etc.
	}

	/**
	 * Render main admin page
	 * Placeholder for admin interface
	 *
	 * @return void
	 */
	public function render_admin_page() {
		// Show success message if colors were redetected
		if ( isset( $_GET['updated'] ) && $_GET['updated'] === 'colors_redetected' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'Colors re-detected successfully!', 'ghsales' ); ?></strong></p>
				<p><?php esc_html_e( 'The default color scheme has been updated with your current Elementor global colors.', 'ghsales' ); ?></p>
			</div>
			<?php
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'GH Sales Foundation Installed Successfully!', 'ghsales' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'Database tables have been created. The admin interface will be built in the next development phase.', 'ghsales' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Note: GDPR consent is managed by external cookie plugins (Cookiebot, CookieYes, etc.). GHSales tracks by default.', 'ghsales' ); ?>
				</p>
			</div>

			<h2><?php esc_html_e( 'Database Status', 'ghsales' ); ?></h2>
			<?php $this->render_database_status(); ?>

			<h2><?php esc_html_e( 'Saved Color Schemes', 'ghsales' ); ?></h2>
			<p>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ghsales&action=redetect_colors' ), 'redetect_colors' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Re-detect Elementor Colors', 'ghsales' ); ?>
				</a>
				<span class="description"><?php esc_html_e( 'Click this to update the default color scheme with your current Elementor global colors.', 'ghsales' ); ?></span>
			</p>
			<?php $this->render_color_schemes(); ?>

			<h2><?php esc_html_e( 'WordPress Options Backup', 'ghsales' ); ?></h2>
			<?php $this->render_options_backup(); ?>

			<h2><?php esc_html_e( 'All Elementor Colors (System + Custom)', 'ghsales' ); ?></h2>
			<?php $this->render_all_elementor_colors(); ?>
		</div>
		<?php
	}

	/**
	 * Render database status table
	 * Shows which tables were created successfully
	 *
	 * @return void
	 */
	private function render_database_status() {
		global $wpdb;

		$tables = array(
			'ghsales_events'         => __( 'Sale Events', 'ghsales' ),
			'ghsales_rules'          => __( 'Sale Rules', 'ghsales' ),
			'ghsales_color_schemes'  => __( 'Color Schemes', 'ghsales' ),
			'ghsales_user_activity'  => __( 'User Activity', 'ghsales' ),
			'ghsales_product_stats'  => __( 'Product Stats', 'ghsales' ),
			'ghsales_upsell_cache'   => __( 'Upsell Cache', 'ghsales' ),
			'ghsales_purchase_limits' => __( 'Purchase Limits', 'ghsales' ),
		);

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Table Name', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Description', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Rows', 'ghsales' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $tables as $table => $description ) {
			$full_table_name = $wpdb->prefix . $table;
			$exists          = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" ) === $full_table_name;
			$row_count       = $exists ? $wpdb->get_var( "SELECT COUNT(*) FROM {$full_table_name}" ) : 0;

			echo '<tr>';
			echo '<td><code>' . esc_html( $full_table_name ) . '</code></td>';
			echo '<td>' . esc_html( $description ) . '</td>';
			echo '<td>';
			if ( $exists ) {
				echo '<span style="color: green;">✓ ' . esc_html__( 'Created', 'ghsales' ) . '</span>';
			} else {
				echo '<span style="color: red;">✗ ' . esc_html__( 'Missing', 'ghsales' ) . '</span>';
			}
			echo '</td>';
			echo '<td>' . esc_html( $row_count ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Enqueue admin assets (CSS/JS)
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		global $post_type;

		// Load on GHSales pages OR Sale Event editor
		$is_ghsales_page = strpos( $hook, 'ghsales' ) !== false;
		$is_event_editor = ( $hook === 'post.php' || $hook === 'post-new.php' ) && $post_type === 'ghsales_event';

		if ( ! $is_ghsales_page && ! $is_event_editor ) {
			return;
		}

		// Enqueue Select2 (comes with WordPress)
		wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
		wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0', true );

		// Admin JS
		wp_enqueue_script( 'ghsales-admin', GHSALES_PLUGIN_URL . 'assets/js/ghsales-admin.js', array( 'jquery', 'select2' ), GHSALES_VERSION, true );

		// Localize script with AJAX URL and nonce for security
		wp_localize_script(
			'ghsales-admin',
			'ghsalesAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ghsales_admin_nonce' ),
			)
		);
	}

	/**
	 * Enqueue frontend assets (CSS/JS)
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Enqueue Swiper CSS (required for minicart upsells carousel)
		wp_enqueue_style(
			'swiper',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
			array(),
			'11.0.5'
		);

		// Enqueue gulcan-plugins product card styles (for minicart upsells)
		$gulcan_plugins_path = WP_PLUGIN_DIR . '/gulcan-plugins/includes/modules/woocommerce-products/assets/css/woocommerce-products-style.css';
		$gulcan_plugins_url = plugins_url( 'gulcan-plugins/includes/modules/woocommerce-products/assets/css/woocommerce-products-style.css' );
		if ( file_exists( $gulcan_plugins_path ) ) {
			wp_enqueue_style(
				'gulcan-wc-products-public',
				$gulcan_plugins_url,
				array( 'swiper' ),
				filemtime( $gulcan_plugins_path )
			);
		}

		// Enqueue gulcan mobile product card styles
		$gulcan_mobile_path = WP_PLUGIN_DIR . '/gulcan-plugins/public/css/gulcan-mobile-product-cards.css';
		$gulcan_mobile_url = plugins_url( 'gulcan-plugins/public/css/gulcan-mobile-product-cards.css' );
		$has_gulcan_mobile = false;
		if ( file_exists( $gulcan_mobile_path ) ) {
			wp_enqueue_style(
				'gulcan-mobile-product-cards',
				$gulcan_mobile_url,
				array( 'gulcan-wc-products-public' ),
				filemtime( $gulcan_mobile_path )
			);
			$has_gulcan_mobile = true;
		}

		// Product badges CSS - High-specificity styles to override WooCommerce badge styling
		$badges_css_path = GHSALES_PLUGIN_DIR . 'public/css/ghsales-product-badges.css';
		if ( file_exists( $badges_css_path ) ) {
			$badges_css_version = filemtime( $badges_css_path );
			wp_enqueue_style( 'ghsales-product-badges', GHSALES_PLUGIN_URL . 'public/css/ghsales-product-badges.css', array(), $badges_css_version );
			error_log( 'GHSales: Enqueued product badges CSS - path: ' . $badges_css_path . ', version: ' . $badges_css_version );
		} else {
			error_log( 'GHSales: WARNING - Product badges CSS file not found at: ' . $badges_css_path );
		}

		// Upsell styles with cache busting - NO DEPENDENCIES to ensure it always loads
		$upsells_css_path = GHSALES_PLUGIN_DIR . 'public/css/ghsales-upsells.css';
		if ( file_exists( $upsells_css_path ) ) {
			$upsells_css_version = filemtime( $upsells_css_path );
			wp_enqueue_style( 'ghsales-upsells', GHSALES_PLUGIN_URL . 'public/css/ghsales-upsells.css', array(), $upsells_css_version );
			error_log( 'GHSales: Enqueued upsells CSS - path: ' . $upsells_css_path . ', version: ' . $upsells_css_version );
		} else {
			error_log( 'GHSales: WARNING - CSS file not found at: ' . $upsells_css_path );
		}

		// Enqueue Swiper JS (required for minicart upsells carousel)
		wp_enqueue_script(
			'swiper',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
			array(),
			'11.0.5',
			true
		);

		// Enqueue gulcan-plugins product scripts (for AJAX add to cart)
		$gulcan_scripts_path = WP_PLUGIN_DIR . '/gulcan-plugins/includes/modules/woocommerce-products/assets/js/woocommerce-products-script.js';
		$gulcan_scripts_url = plugins_url( 'gulcan-plugins/includes/modules/woocommerce-products/assets/js/woocommerce-products-script.js' );
		if ( file_exists( $gulcan_scripts_path ) ) {
			wp_enqueue_script(
				'gulcan-wc-products-public',
				$gulcan_scripts_url,
				array( 'jquery', 'swiper' ),
				filemtime( $gulcan_scripts_path ),
				true
			);

			// Localize gulcan-plugins script for AJAX
			wp_localize_script(
				'gulcan-wc-products-public',
				'gulcan_wc_products',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'gulcan_wc_products_nonce' ),
					'cart_url'        => wc_get_cart_url(),
					'currency_symbol' => get_woocommerce_currency_symbol(),
					'strings'         => array(
						'loading'      => __( 'Loading products...', 'ghsales' ),
						'error'        => __( 'Error loading products', 'ghsales' ),
						'no_products'  => __( 'No products found', 'ghsales' ),
						'add_to_cart'  => __( 'Add to Cart', 'ghsales' ),
						'read_more'    => __( 'View Product', 'ghsales' ),
						'sale'         => __( 'Sale!', 'ghsales' ),
					),
				)
			);
		}

		// Upsell JavaScript (AJAX add-to-cart)
		wp_enqueue_script( 'ghsales-upsells', GHSALES_PLUGIN_URL . 'public/js/ghsales-upsells.js', array( 'jquery', 'wc-add-to-cart', 'gulcan-wc-products-public' ), GHSALES_VERSION, true );

		// Frontend JS for BOGO quantity display in mini cart
		wp_enqueue_script( 'ghsales-frontend', GHSALES_PLUGIN_URL . 'assets/js/ghsales-frontend.js', array( 'jquery' ), GHSALES_VERSION, true );

		// Localize script for AJAX
		wp_localize_script(
			'ghsales-upsells',
			'ghsales_upsell_params',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ghsales_upsell_nonce' ),
			)
		);

		// Enqueue translations to JavaScript
		GHSales_i18n::enqueue_js_translations();
	}

	/**
	 * Initialize WooCommerce integration
	 * Sets up hooks for cart, checkout, product pages, etc.
	 *
	 * @return void
	 */
	public function init_woocommerce_integration() {
		// WooCommerce integration will be added in future phases
		// Examples:
		// - Hook into cart calculations for discounts
		// - Add upsells to product pages
		// - Track add-to-cart events
		// - Integrate with checkout process
	}

	/**
	 * Handle admin page actions
	 * Processes button clicks and admin actions
	 *
	 * @return void
	 */
	public function handle_admin_actions() {
		// Check if we have an action parameter
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['page'] ) || $_GET['page'] !== 'ghsales' ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

		// Handle color re-detection
		if ( $action === 'redetect_colors' ) {
			// Verify nonce
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'redetect_colors' ) ) {
				wp_die( esc_html__( 'Security check failed', 'ghsales' ) );
			}

			// Check user capabilities
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action', 'ghsales' ) );
			}

			// Load installer class
			require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-installer.php';

			// Re-detect colors using installer method
			$colors = $this->get_current_site_colors_wrapper();

			// Update the default color scheme in database
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'ghsales_color_schemes',
				array(
					'primary_color'    => $colors['primary'],
					'secondary_color'  => $colors['secondary'],
					'accent_color'     => $colors['accent'],
					'text_color'       => $colors['text'],
					'background_color' => $colors['background'],
				),
				array( 'scheme_name' => 'Default Theme Colors' ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);

			// Update backup in wp_options
			update_option( 'ghsales_original_colors', $colors, false );

			// Redirect with success message
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'ghsales',
						'updated' => 'colors_redetected',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	/**
	 * Wrapper to call installer's color detection method
	 * This is needed because the installer method is private static
	 *
	 * @return array Detected colors
	 */
	private function get_current_site_colors_wrapper() {
		$colors = array(
			'primary'    => '#3498db',
			'secondary'  => '#2c3e50',
			'accent'     => '#e74c3c',
			'text'       => '#333333',
			'background' => '#ffffff',
		);

		$all_elementor_colors = array(); // Store ALL colors for import

		// Try to get Elementor global colors if Elementor is active
		if ( class_exists( '\Elementor\Plugin' ) ) {
			// Try newer Elementor Kit settings first (Elementor 3.0+)
			$kit_id = get_option( 'elementor_active_kit' );

			if ( $kit_id ) {
				$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

				// Get System Colors
				if ( ! empty( $kit_settings['system_colors'] ) ) {
					$system_colors = $kit_settings['system_colors'];

					// Map Elementor system colors to our naming
					foreach ( $system_colors as $color_item ) {
						if ( isset( $color_item['_id'] ) && isset( $color_item['color'] ) ) {
							$elementor_id = $color_item['_id'];
							$hex_color    = $color_item['color'];
							$color_title  = isset( $color_item['title'] ) ? $color_item['title'] : $elementor_id;

							// Store in all colors array
							$all_elementor_colors[ $elementor_id ] = array(
								'color' => $hex_color,
								'title' => $color_title,
								'type'  => 'system',
							);

							// Map Elementor IDs to our default scheme keys
							switch ( $elementor_id ) {
								case 'primary':
									$colors['primary'] = $hex_color;
									break;
								case 'secondary':
									$colors['secondary'] = $hex_color;
									break;
								case 'text':
									$colors['text'] = $hex_color;
									break;
								case 'accent':
									$colors['accent'] = $hex_color;
									break;
							}
						}
					}
				}

				// Get Custom Colors
				if ( ! empty( $kit_settings['custom_colors'] ) ) {
					$custom_colors = $kit_settings['custom_colors'];

					foreach ( $custom_colors as $color_item ) {
						if ( isset( $color_item['_id'] ) && isset( $color_item['color'] ) ) {
							$color_id    = $color_item['_id'];
							$hex_color   = $color_item['color'];
							$color_title = isset( $color_item['title'] ) ? $color_item['title'] : $color_id;

							// Store in all colors array
							$all_elementor_colors[ $color_id ] = array(
								'color' => $hex_color,
								'title' => $color_title,
								'type'  => 'custom',
							);
						}
					}
				}

				// Store all colors in transient for display
				set_transient( 'ghsales_all_elementor_colors', $all_elementor_colors, HOUR_IN_SECONDS );
			}
		}

		return $colors;
	}

	/**
	 * Render saved color schemes from database
	 * Shows what colors were detected and saved
	 *
	 * @return void
	 */
	private function render_color_schemes() {
		global $wpdb;

		$schemes = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}ghsales_color_schemes ORDER BY created_at DESC"
		);

		if ( empty( $schemes ) ) {
			echo '<p>' . esc_html__( 'No color schemes found in database.', 'ghsales' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Scheme Name', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Primary', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Secondary', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Accent', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Text', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Background', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Active', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Created', 'ghsales' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $schemes as $scheme ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $scheme->scheme_name ) . '</strong></td>';

			// Show color swatches
			$colors = array(
				'primary_color'     => $scheme->primary_color,
				'secondary_color'   => $scheme->secondary_color,
				'accent_color'      => $scheme->accent_color,
				'text_color'        => $scheme->text_color,
				'background_color'  => $scheme->background_color,
			);

			foreach ( $colors as $color ) {
				echo '<td>';
				echo '<div style="display: flex; align-items: center; gap: 8px;">';
				echo '<div style="width: 30px; height: 30px; background-color: ' . esc_attr( $color ) . '; border: 1px solid #ccc; border-radius: 3px;"></div>';
				echo '<code>' . esc_html( $color ) . '</code>';
				echo '</div>';
				echo '</td>';
			}

			echo '<td>' . ( $scheme->is_active ? '<span style="color: green;">✓ Active</span>' : '-' ) . '</td>';
			echo '<td>' . esc_html( $scheme->created_at ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render WordPress options backup
	 * Shows what was saved in wp_options for restoration
	 *
	 * @return void
	 */
	private function render_options_backup() {
		$original_colors = get_option( 'ghsales_original_colors', array() );

		if ( empty( $original_colors ) ) {
			echo '<div class="notice notice-warning inline">';
			echo '<p>' . esc_html__( 'No color backup found in WordPress options. This might mean Elementor was not active during installation, or the backup failed.', 'ghsales' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<div class="notice notice-success inline">';
		echo '<p><strong>' . esc_html__( 'Original colors backed up successfully!', 'ghsales' ) . '</strong></p>';
		echo '</div>';

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Color Name', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Hex Value', 'ghsales' ) . '</th>';
		echo '<th>' . esc_html__( 'Preview', 'ghsales' ) . '</th>';
		echo '</tr></thead><tbody>';

		$color_labels = array(
			'primary'    => __( 'Primary Color', 'ghsales' ),
			'secondary'  => __( 'Secondary Color', 'ghsales' ),
			'accent'     => __( 'Accent Color', 'ghsales' ),
			'text'       => __( 'Text Color', 'ghsales' ),
			'background' => __( 'Background Color', 'ghsales' ),
		);

		foreach ( $original_colors as $key => $color ) {
			echo '<tr>';
			echo '<td><strong>' . esc_html( $color_labels[ $key ] ?? $key ) . '</strong></td>';
			echo '<td><code>' . esc_html( $color ) . '</code></td>';
			echo '<td><div style="width: 50px; height: 50px; background-color: ' . esc_attr( $color ) . '; border: 2px solid #333; border-radius: 5px;"></div></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		echo '<p style="margin-top: 15px;">';
		echo '<em>' . esc_html__( 'These colors will be automatically restored when color schemes are deactivated or the plugin is uninstalled.', 'ghsales' ) . '</em>';
		echo '</p>';
	}

	/**
	 * Render all Elementor colors (system + custom)
	 * Shows complete color palette from Elementor
	 *
	 * @return void
	 */
	private function render_all_elementor_colors() {
		$all_colors = get_transient( 'ghsales_all_elementor_colors' );

		if ( empty( $all_colors ) ) {
			echo '<div class="notice notice-info inline">';
			echo '<p>' . esc_html__( 'No Elementor colors detected yet. Click "Re-detect Elementor Colors" button above to scan your Elementor color palette.', 'ghsales' ) . '</p>';
			echo '</div>';
			return;
		}

		// Separate system and custom colors
		$system_colors = array_filter( $all_colors, function( $item ) {
			return $item['type'] === 'system';
		});

		$custom_colors = array_filter( $all_colors, function( $item ) {
			return $item['type'] === 'custom';
		});

		echo '<p class="description">';
		printf(
			/* translators: %1$d: number of system colors, %2$d: number of custom colors */
			esc_html__( 'Detected %1$d system colors and %2$d custom colors from your Elementor global palette.', 'ghsales' ),
			count( $system_colors ),
			count( $custom_colors )
		);
		echo '</p>';

		// System Colors Table
		if ( ! empty( $system_colors ) ) {
			echo '<h3>' . esc_html__( 'System Colors', 'ghsales' ) . '</h3>';
			echo '<table class="widefat striped" style="max-width: 800px;">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Color Name', 'ghsales' ) . '</th>';
			echo '<th>' . esc_html__( 'Hex Value', 'ghsales' ) . '</th>';
			echo '<th>' . esc_html__( 'Preview', 'ghsales' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $system_colors as $color_id => $color_data ) {
				echo '<tr>';
				echo '<td><strong>' . esc_html( $color_data['title'] ) . '</strong> <code style="color: #999;">(' . esc_html( $color_id ) . ')</code></td>';
				echo '<td><code style="font-size: 14px;">' . esc_html( $color_data['color'] ) . '</code></td>';
				echo '<td><div style="width: 60px; height: 40px; background-color: ' . esc_attr( $color_data['color'] ) . '; border: 2px solid #ddd; border-radius: 4px;"></div></td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		// Custom Colors Table
		if ( ! empty( $custom_colors ) ) {
			echo '<h3 style="margin-top: 25px;">' . esc_html__( 'Custom Colors', 'ghsales' ) . '</h3>';
			echo '<table class="widefat striped" style="max-width: 800px;">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Color Name', 'ghsales' ) . '</th>';
			echo '<th>' . esc_html__( 'Hex Value', 'ghsales' ) . '</th>';
			echo '<th>' . esc_html__( 'Preview', 'ghsales' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $custom_colors as $color_id => $color_data ) {
				echo '<tr>';
				echo '<td><strong>' . esc_html( $color_data['title'] ) . '</strong></td>';
				echo '<td><code style="font-size: 14px;">' . esc_html( $color_data['color'] ) . '</code></td>';
				echo '<td><div style="width: 60px; height: 40px; background-color: ' . esc_attr( $color_data['color'] ) . '; border: 2px solid #ddd; border-radius: 4px;"></div></td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		// Future feature hint
		echo '<div class="notice notice-info inline" style="margin-top: 20px;">';
		echo '<p><strong>' . esc_html__( 'Coming Soon:', 'ghsales' ) . '</strong> ';
		echo esc_html__( 'You\'ll be able to create custom color schemes from these colors with a visual picker in the next development phase!', 'ghsales' );
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Prevent cloning of singleton
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of singleton
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}
