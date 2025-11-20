<?php
/**
 * GHSales Database Installer
 *
 * Handles plugin installation, database table creation, and initial setup.
 * Creates all 7 required tables with proper schema, indexes, and foreign keys.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_Installer Class
 *
 * Manages database setup and plugin installation
 */
class GHSales_Installer {

	/**
	 * Run the installer
	 * Called during plugin activation
	 *
	 * @return void
	 */
	public static function install() {
		// Check if we're running this for the first time or updating
		$installed_version = get_option( 'ghsales_version', '' );

		// Create/update database tables
		self::create_tables();

		// Seed default data (only on first install)
		if ( empty( $installed_version ) ) {
			self::seed_default_data();
		}

		// Update plugin version in database
		update_option( 'ghsales_version', GHSALES_VERSION );

		// Schedule cron jobs
		self::schedule_cron_jobs();

		// Trigger activation hook for other components
		do_action( 'ghsales_activated' );
	}

	/**
	 * Schedule cron jobs for stats management
	 *
	 * @return void
	 */
	private static function schedule_cron_jobs() {
		// Load stats class
		require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-stats.php';

		// Schedule all cron jobs
		GHSales_Stats::schedule_cron_jobs();

		error_log( 'GHSales: Cron jobs scheduled successfully' );
	}

	/**
	 * Create all database tables
	 * Uses WordPress dbDelta function for safe table creation/updates
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		// WordPress database charset and collation
		$charset_collate = $wpdb->get_charset_collate();

		// SQL statements for all 7 tables
		$sql = array();

		/**
		 * Table 1: Sale Events
		 * Stores sale event definitions (Black Friday, Halloween, etc.)
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_events (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_name VARCHAR(255) NOT NULL,
			event_type VARCHAR(50) NOT NULL DEFAULT 'manual',
			start_date DATETIME NOT NULL,
			end_date DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'draft',
			color_scheme_id BIGINT UNSIGNED NULL,
			settings LONGTEXT NULL,
			allow_stacking TINYINT(1) NOT NULL DEFAULT 0,
			apply_on_sale_price TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_status (status),
			KEY idx_dates (start_date, end_date),
			KEY idx_color_scheme (color_scheme_id)
		) $charset_collate;";

		/**
		 * Table 2: Sale Rules
		 * Individual discount rules for each event (1 event can have many rules)
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_rules (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT UNSIGNED NOT NULL,
			rule_type VARCHAR(50) NOT NULL,
			applies_to VARCHAR(50) NOT NULL,
			target_ids TEXT NULL,
			discount_value DECIMAL(10,2) NOT NULL,
			conditions LONGTEXT NULL,
			priority INT NOT NULL DEFAULT 0,
			max_quantity_per_customer INT UNSIGNED NULL,
			PRIMARY KEY (id),
			KEY idx_event (event_id)
		) $charset_collate;";

		/**
		 * Table 3: Color Schemes
		 * Color palette definitions for sitewide theming
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_color_schemes (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scheme_name VARCHAR(255) NOT NULL,
			primary_color VARCHAR(7) NOT NULL,
			secondary_color VARCHAR(7) NOT NULL,
			accent_color VARCHAR(7) NOT NULL,
			text_color VARCHAR(7) NOT NULL,
			background_color VARCHAR(7) NOT NULL,
			colors_json LONGTEXT DEFAULT NULL COMMENT 'JSON storage for all Elementor colors (system + custom)',
			is_active TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_active (is_active)
		) $charset_collate;";

		/**
		 * Table 4: User Activity
		 * Track user behavior (views, searches, clicks) for personalization
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_user_activity (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(100) NOT NULL,
			user_id BIGINT UNSIGNED NULL,
			activity_type VARCHAR(50) NOT NULL,
			product_id BIGINT UNSIGNED NULL,
			category_id BIGINT UNSIGNED NULL,
			search_query VARCHAR(255) NULL,
			meta_data LONGTEXT NULL,
			ip_address VARCHAR(45) NULL,
			user_agent TEXT NULL,
			timestamp DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_session (session_id),
			KEY idx_user (user_id),
			KEY idx_product (product_id),
			KEY idx_timestamp (timestamp),
			KEY idx_activity_type (activity_type)
		) $charset_collate;";

		/**
		 * Table 5: Product Stats
		 * Aggregated product performance metrics for analytics and smart pricing
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_product_stats (
			product_id BIGINT UNSIGNED NOT NULL,
			views_total INT UNSIGNED NOT NULL DEFAULT 0,
			views_7days INT UNSIGNED NOT NULL DEFAULT 0,
			views_30days INT UNSIGNED NOT NULL DEFAULT 0,
			conversions_total INT UNSIGNED NOT NULL DEFAULT 0,
			conversions_7days INT UNSIGNED NOT NULL DEFAULT 0,
			revenue_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			profit_margin DECIMAL(5,2) NULL,
			last_updated DATETIME NOT NULL,
			PRIMARY KEY (product_id),
			KEY idx_views_7days (views_7days),
			KEY idx_conversions_7days (conversions_7days)
		) $charset_collate;";

		/**
		 * Table 6: Upsell Cache
		 * Cache calculated upsell recommendations for performance
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_upsell_cache (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			context_type VARCHAR(50) NOT NULL,
			context_id BIGINT UNSIGNED NULL,
			user_id BIGINT UNSIGNED NULL,
			session_id VARCHAR(100) NULL,
			recommended_products TEXT NOT NULL,
			expires_at DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_context (context_type, context_id),
			KEY idx_user (user_id),
			KEY idx_session (session_id),
			KEY idx_expires (expires_at)
		) $charset_collate;";

		/**
		 * Table 7: Purchase Limits
		 * Track purchase limits per customer for sale rules
		 */
		$sql[] = "CREATE TABLE {$wpdb->prefix}ghsales_purchase_limits (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_id BIGINT UNSIGNED NOT NULL,
			customer_identifier VARCHAR(255) NOT NULL,
			quantity_purchased INT UNSIGNED NOT NULL DEFAULT 0,
			last_updated DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY idx_rule_customer (rule_id, customer_identifier),
			KEY idx_customer (customer_identifier)
		) $charset_collate;";

		// Load WordPress database upgrade functions
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create/update tables using dbDelta (safe for updates)
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		// Log successful installation
		error_log( 'GHSales: Database tables created/updated successfully' );
	}

	/**
	 * Seed default data on first installation
	 * Creates a default color scheme based on current site colors
	 *
	 * @return void
	 */
	private static function seed_default_data() {
		global $wpdb;

		// Get current Elementor colors if available
		$default_colors = self::get_current_site_colors();

		// Insert default color scheme
		$wpdb->insert(
			$wpdb->prefix . 'ghsales_color_schemes',
			array(
				'scheme_name'       => __( 'Default Theme Colors', 'ghsales' ),
				'primary_color'     => $default_colors['primary'],
				'secondary_color'   => $default_colors['secondary'],
				'accent_color'      => $default_colors['accent'],
				'text_color'        => $default_colors['text'],
				'background_color'  => $default_colors['background'],
				'is_active'         => 1, // Set as active by default
				'created_at'        => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		// Store original colors for future restoration
		update_option(
			'ghsales_original_colors',
			$default_colors,
			false // Don't autoload (only needed occasionally)
		);

		// Log default data seeding
		error_log( 'GHSales: Default color scheme created' );
	}

	/**
	 * Get current site colors from Elementor or use sensible defaults
	 *
	 * @return array Associative array of color values
	 */
	private static function get_current_site_colors() {
		$colors = array(
			'primary'    => '#3498db', // Blue (fallback)
			'secondary'  => '#2c3e50', // Dark blue-gray (fallback)
			'accent'     => '#e74c3c', // Red (fallback)
			'text'       => '#333333', // Dark gray (fallback)
			'background' => '#ffffff', // White (fallback)
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
							$hex_color = $color_item['color'];

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

					error_log( 'GHSales: Detected Elementor colors from Kit settings' );
				}
			}

			// Fallback to old color scheme format (Elementor < 3.0)
			if ( $colors['primary'] === '#3498db' ) { // Still using fallback
				$elementor_colors = get_option( 'elementor_scheme_color', array() );

				if ( ! empty( $elementor_colors ) ) {
					// Map old Elementor color slots to our naming convention
					$color_map = array(
						'primary'    => 1, // Elementor slot 1 = Primary
						'secondary'  => 2, // Elementor slot 2 = Secondary
						'accent'     => 3, // Elementor slot 3 = Accent
						'text'       => 4, // Elementor slot 4 = Text
					);

					foreach ( $color_map as $key => $slot ) {
						if ( ! empty( $elementor_colors[ $slot ] ) ) {
							$colors[ $key ] = $elementor_colors[ $slot ];
						}
					}

					error_log( 'GHSales: Detected Elementor colors from legacy scheme' );
				}
			}
		}

		// Log what we detected
		error_log( 'GHSales: Final detected colors: ' . print_r( $colors, true ) );

		return $colors;
	}

	/**
	 * Clean up plugin data on uninstall
	 * This is called from uninstall.php (only if user chooses to delete data)
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		// Delete all plugin tables
		$tables = array(
			'ghsales_purchase_limits',
			'ghsales_upsell_cache',
			'ghsales_user_activity',
			'ghsales_product_stats',
			'ghsales_rules',
			'ghsales_events',
			'ghsales_color_schemes',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
		}

		// Delete all plugin options
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ghsales_%'" );

		// Log cleanup
		error_log( 'GHSales: Plugin data cleaned up on uninstall' );
	}

	/**
	 * Update database schema on plugin update
	 * Checks version and runs necessary migrations
	 *
	 * @return void
	 */
	public static function update() {
		$current_version  = get_option( 'ghsales_version', '0.0.0' );
		$latest_version   = GHSALES_VERSION;

		// If versions match, no update needed
		if ( version_compare( $current_version, $latest_version, '>=' ) ) {
			return;
		}

		// Run migrations based on version changes
		// Example: if ( version_compare( $current_version, '1.1.0', '<' ) ) { self::migrate_to_110(); }

		// Recreate tables (dbDelta will update schema if changed)
		self::create_tables();

		// Update version number
		update_option( 'ghsales_version', $latest_version );

		// Log update
		error_log( "GHSales: Updated from v{$current_version} to v{$latest_version}" );
	}
}
