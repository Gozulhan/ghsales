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
	 * Detected GDPR plugin name (if any)
	 *
	 * @var string|null
	 */
	private $gdpr_plugin = null;

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
		// Detect GDPR/cookie consent plugins
		$this->detect_gdpr_plugin();

		// Initialize plugin components
		$this->init_hooks();

		// Load plugin components (will be added in future phases)
		// $this->load_components();
	}

	/**
	 * Detect active GDPR/cookie consent plugins
	 * Checks for popular cookie consent plugins to integrate with
	 *
	 * @return void
	 */
	private function detect_gdpr_plugin() {
		// Check for Cookiebot
		if ( class_exists( 'Cookiebot_WP' ) || is_plugin_active( 'cookiebot/cookiebot.php' ) ) {
			$this->gdpr_plugin = 'cookiebot';
			error_log( 'GHSales: Detected Cookiebot - will use for consent management' );
			return;
		}

		// Check for CookieYes (GDPR Cookie Consent)
		if ( class_exists( 'CookieYes' ) || is_plugin_active( 'cookie-law-info/cookie-law-info.php' ) ) {
			$this->gdpr_plugin = 'cookieyes';
			error_log( 'GHSales: Detected CookieYes - will use for consent management' );
			return;
		}

		// Check for Complianz
		if ( class_exists( 'COMPLIANZ' ) || is_plugin_active( 'complianz-gdpr/complianz-gdpr.php' ) ) {
			$this->gdpr_plugin = 'complianz';
			error_log( 'GHSales: Detected Complianz - will use for consent management' );
			return;
		}

		// Check for GDPR Cookie Compliance
		if ( is_plugin_active( 'gdpr-cookie-compliance/moove-gdpr.php' ) ) {
			$this->gdpr_plugin = 'moove_gdpr';
			error_log( 'GHSales: Detected Moove GDPR - will use for consent management' );
			return;
		}

		// No GDPR plugin detected - we'll use our own consent banner
		$this->gdpr_plugin = null;
		error_log( 'GHSales: No GDPR plugin detected - will use built-in consent banner' );
	}

	/**
	 * Get detected GDPR plugin name
	 *
	 * @return string|null Plugin identifier or null if none detected
	 */
	public function get_gdpr_plugin() {
		return $this->gdpr_plugin;
	}

	/**
	 * Check if user has given consent for analytics tracking
	 * Integrates with detected GDPR plugin or uses our own consent log
	 *
	 * @return bool True if consent given, false otherwise
	 */
	public function has_analytics_consent() {
		// Check based on detected GDPR plugin
		switch ( $this->gdpr_plugin ) {
			case 'cookiebot':
				return $this->check_cookiebot_consent();

			case 'cookieyes':
				return $this->check_cookieyes_consent();

			case 'complianz':
				return $this->check_complianz_consent();

			case 'moove_gdpr':
				return $this->check_moove_gdpr_consent();

			default:
				// Use our own consent check (will implement in GDPR class)
				return $this->check_ghsales_consent();
		}
	}

	/**
	 * Check Cookiebot consent status
	 *
	 * @return bool
	 */
	private function check_cookiebot_consent() {
		// Cookiebot stores consent in cookie: CookieConsent
		// Format: {statistics:true, marketing:true, necessary:true}
		if ( ! isset( $_COOKIE['CookieConsent'] ) ) {
			return false;
		}

		// Decode consent cookie
		$consent = json_decode( stripslashes( $_COOKIE['CookieConsent'] ), true );

		// We need 'statistics' consent for analytics
		return ! empty( $consent['statistics'] );
	}

	/**
	 * Check CookieYes consent status
	 *
	 * @return bool
	 */
	private function check_cookieyes_consent() {
		// CookieYes stores consent in cookie: cookieyes-consent
		// Format: consent:yes, analytics:yes
		if ( ! isset( $_COOKIE['cookieyes-consent'] ) ) {
			return false;
		}

		$consent = $_COOKIE['cookieyes-consent'];

		// Check if analytics category is accepted
		return strpos( $consent, 'analytics:yes' ) !== false;
	}

	/**
	 * Check Complianz consent status
	 *
	 * @return bool
	 */
	private function check_complianz_consent() {
		// Complianz stores consent in cookie: complianz_consent_status
		// Format: {statistics:allow, marketing:allow}
		if ( ! isset( $_COOKIE['complianz_consent_status'] ) ) {
			return false;
		}

		$consent = json_decode( stripslashes( $_COOKIE['complianz_consent_status'] ), true );

		// We need 'statistics' consent
		return ! empty( $consent['statistics'] ) && $consent['statistics'] === 'allow';
	}

	/**
	 * Check Moove GDPR consent status
	 *
	 * @return bool
	 */
	private function check_moove_gdpr_consent() {
		// Moove GDPR stores consent in cookie: moove_gdpr_popup
		// Format: {thirdparty:1, advanced:1}
		if ( ! isset( $_COOKIE['moove_gdpr_popup'] ) ) {
			return false;
		}

		$consent = json_decode( stripslashes( $_COOKIE['moove_gdpr_popup'] ), true );

		// Check for third-party/analytics consent
		return ! empty( $consent['thirdparty'] );
	}

	/**
	 * Check GHSales own consent status
	 * Falls back to our database consent log
	 *
	 * @return bool
	 */
	private function check_ghsales_consent() {
		global $wpdb;

		// Get session ID (WooCommerce session)
		$session_id = $this->get_session_id();
		if ( ! $session_id ) {
			return false;
		}

		// Check consent log for this session
		$consent = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT consent_given FROM {$wpdb->prefix}ghsales_consent_log
				WHERE session_id = %s AND consent_type = 'analytics'
				ORDER BY consent_date DESC LIMIT 1",
				$session_id
			)
		);

		return ! empty( $consent );
	}

	/**
	 * Get current session ID
	 * Uses WooCommerce session if available, creates fallback session
	 *
	 * @return string|null
	 */
	private function get_session_id() {
		// Try WooCommerce session first
		if ( function_exists( 'WC' ) && WC()->session ) {
			$customer = WC()->session->get_customer_id();
			if ( $customer ) {
				return 'wc_' . $customer;
			}
		}

		// Fallback: Use WordPress session or create one
		if ( ! session_id() ) {
			// Don't start session here - will be handled by GDPR class
			return null;
		}

		return 'wp_' . session_id();
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

		// AJAX hooks (for future features)
		add_action( 'wp_ajax_ghsales_save_consent', array( $this, 'ajax_save_consent' ) );
		add_action( 'wp_ajax_nopriv_ghsales_save_consent', array( $this, 'ajax_save_consent' ) );
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
					<?php
					printf(
						/* translators: %s: Detected GDPR plugin name or 'None' */
						esc_html__( 'Detected GDPR Plugin: %s', 'ghsales' ),
						'<code>' . ( $this->gdpr_plugin ? esc_html( $this->gdpr_plugin ) : esc_html__( 'None (will use built-in banner)', 'ghsales' ) ) . '</code>'
					);
					?>
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
			'ghsales_consent_log'    => __( 'Consent Log', 'ghsales' ),
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
		// Only load on GHSales admin pages
		if ( strpos( $hook, 'ghsales' ) === false ) {
			return;
		}

		// Admin CSS (will create in future phase)
		// wp_enqueue_style( 'ghsales-admin', GHSALES_PLUGIN_URL . 'assets/css/ghsales-admin.css', array(), GHSALES_VERSION );

		// Admin JS (will create in future phase)
		// wp_enqueue_script( 'ghsales-admin', GHSALES_PLUGIN_URL . 'assets/js/ghsales-admin.js', array( 'jquery' ), GHSALES_VERSION, true );
	}

	/**
	 * Enqueue frontend assets (CSS/JS)
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Frontend CSS (will create in future phase)
		// wp_enqueue_style( 'ghsales-frontend', GHSALES_PLUGIN_URL . 'assets/css/ghsales-frontend.css', array(), GHSALES_VERSION );

		// Frontend JS (will create in future phase)
		// wp_enqueue_script( 'ghsales-frontend', GHSALES_PLUGIN_URL . 'assets/js/ghsales-frontend.js', array( 'jquery' ), GHSALES_VERSION, true );
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

		// Try to get Elementor global colors if Elementor is active
		if ( class_exists( '\Elementor\Plugin' ) ) {
			// Try newer Elementor Kit settings first (Elementor 3.0+)
			$kit_id = get_option( 'elementor_active_kit' );

			if ( $kit_id ) {
				$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

				if ( ! empty( $kit_settings['system_colors'] ) ) {
					$system_colors = $kit_settings['system_colors'];

					// Map Elementor system colors to our naming
					foreach ( $system_colors as $color_item ) {
						if ( isset( $color_item['_id'] ) && isset( $color_item['color'] ) ) {
							$elementor_id = $color_item['_id'];
							$hex_color    = $color_item['color'];

							// Map Elementor IDs to our keys
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
			}
		}

		return $colors;
	}

	/**
	 * AJAX handler for saving user consent
	 * Processes consent choices from banner
	 *
	 * @return void
	 */
	public function ajax_save_consent() {
		// Will be implemented in GDPR class (future phase)
		wp_send_json_error( array( 'message' => 'Not implemented yet' ) );
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
