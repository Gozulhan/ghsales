<?php
/**
 * Plugin Name: GH Sales
 * Plugin URI: https://gulcanhome.eu
 * Description: Comprehensive WordPress/WooCommerce plugin for sales event management, intelligent upselling, and sitewide visual theming with GDPR-compliant user tracking.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Gulcan Home Development Team
 * Author URI: https://gulcanhome.eu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ghsales
 * Domain Path: /languages
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package GHSales
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define plugin constants
 * These constants are used throughout the plugin for paths, URLs, and versioning
 */
define( 'GHSALES_VERSION', '1.0.0' );
define( 'GHSALES_PLUGIN_FILE', __FILE__ );
define( 'GHSALES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GHSALES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GHSALES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active
 * GHSales requires WooCommerce to function properly
 *
 * @return bool True if WooCommerce is active, false otherwise
 */
function ghsales_is_woocommerce_active() {
	// Check if WooCommerce class exists (loaded before our plugin)
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}

	// Check in active plugins list (for edge cases)
	$active_plugins = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
	}

	return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
}

/**
 * Display admin notice if WooCommerce is not active
 * This prevents the plugin from running without its required dependency
 */
function ghsales_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>GH Sales</strong> requires WooCommerce to be installed and active. Please install and activate <a href="%s" target="_blank">WooCommerce</a> first.', 'ghsales' ),
					'https://wordpress.org/plugins/woocommerce/'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Plugin activation hook
 * Runs when plugin is activated for the first time
 */
function ghsales_activate() {
	// Check WooCommerce dependency before activating
	if ( ! ghsales_is_woocommerce_active() ) {
		// Deactivate plugin immediately if WooCommerce is not active
		deactivate_plugins( GHSALES_PLUGIN_BASENAME );
		wp_die(
			wp_kses_post( __( '<strong>GH Sales</strong> requires WooCommerce to be installed and active. Please install WooCommerce first.', 'ghsales' ) ),
			esc_html__( 'Plugin Activation Error', 'ghsales' ),
			array( 'back_link' => true )
		);
	}

	// Load installer class
	require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-installer.php';

	// Run database installation
	GHSales_Installer::install();

	// Store activation timestamp for future reference
	update_option( 'ghsales_activated_at', time() );

	// Flush rewrite rules for any custom post types we'll create
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ghsales_activate' );

/**
 * Plugin deactivation hook
 * Runs when plugin is deactivated (but not deleted)
 */
function ghsales_deactivate() {
	// Unschedule cron jobs
	require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-stats.php';
	GHSales_Stats::unschedule_cron_jobs();

	// Flush rewrite rules to clean up
	flush_rewrite_rules();

	// Note: We do NOT delete data here
	// Data cleanup only happens on uninstall (see uninstall.php)
}
register_deactivation_hook( __FILE__, 'ghsales_deactivate' );

/**
 * Initialize the plugin
 * This function runs after all plugins are loaded
 */
function ghsales_init() {
	// Double-check WooCommerce is active (in case it was deactivated after our plugin)
	if ( ! ghsales_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'ghsales_woocommerce_missing_notice' );
		return;
	}

	// Load text domain for translations
	load_plugin_textdomain( 'ghsales', false, dirname( GHSALES_PLUGIN_BASENAME ) . '/languages' );

	// Load core class
	require_once GHSALES_PLUGIN_DIR . 'includes/class-ghsales-core.php';

	// Initialize plugin core (singleton pattern)
	GHSales_Core::instance();
}
add_action( 'plugins_loaded', 'ghsales_init', 10 );

/**
 * Plugin action links
 * Adds quick links to plugin list page (Settings, Docs, etc.)
 *
 * @param array $links Existing plugin action links
 * @return array Modified plugin action links
 */
function ghsales_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=ghsales' ) . '">' . esc_html__( 'Settings', 'ghsales' ) . '</a>',
		'<a href="https://gulcanhome.eu/docs/ghsales/" target="_blank">' . esc_html__( 'Docs', 'ghsales' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . GHSALES_PLUGIN_BASENAME, 'ghsales_plugin_action_links' );

/**
 * Display admin notice after successful activation
 * Shows helpful next steps to guide the user
 */
function ghsales_activation_notice() {
	// Only show on admin pages, and only once
	if ( ! is_admin() || ! get_transient( 'ghsales_activation_notice' ) ) {
		return;
	}

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong><?php esc_html_e( 'GH Sales activated successfully!', 'ghsales' ); ?></strong>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: Settings page link */
					__( 'Get started by creating your first sale event. <a href="%s">Go to Settings &rarr;</a>', 'ghsales' ),
					admin_url( 'admin.php?page=ghsales' )
				)
			);
			?>
		</p>
	</div>
	<?php

	// Delete transient so notice only shows once
	delete_transient( 'ghsales_activation_notice' );
}
add_action( 'admin_notices', 'ghsales_activation_notice' );

/**
 * Set transient for activation notice
 * This runs during activation to trigger the notice on next admin page load
 */
function ghsales_set_activation_notice() {
	set_transient( 'ghsales_activation_notice', true, 60 );
}
add_action( 'ghsales_activated', 'ghsales_set_activation_notice' );
