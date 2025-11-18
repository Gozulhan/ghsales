<?php
/**
 * GHSales Uninstall Script
 *
 * Runs when plugin is uninstalled (deleted) from WordPress.
 * Cleans up database tables and options if user chooses to delete data.
 *
 * IMPORTANT: This only runs when plugin is UNINSTALLED (deleted), not deactivated.
 * Deactivation keeps all data intact.
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly or not via WordPress uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Uninstall options
 * Admin can choose to keep or delete data on uninstall
 */
$delete_data = get_option( 'ghsales_delete_data_on_uninstall', false );

// If admin chose NOT to delete data, exit without cleanup
if ( ! $delete_data ) {
	// Log that we're keeping data
	error_log( 'GHSales: Plugin uninstalled but data retained (user preference)' );
	return;
}

/**
 * User chose to delete all data - proceed with cleanup
 */

// Load installer class for cleanup method
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ghsales-installer.php';

// Run uninstall cleanup
GHSales_Installer::uninstall();

// Additional cleanup not covered by installer

global $wpdb;

/**
 * Clean up post meta (if we stored any in future phases)
 * Example: Sale event meta on products, color scheme previews, etc.
 */
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'ghsales_%'" );

/**
 * Clean up user meta (if we stored any)
 * Example: User preferences, last viewed products, etc.
 */
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ghsales_%'" );

/**
 * Clean up transients
 * WordPress uses transients for temporary cached data
 */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ghsales_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ghsales_%'" );

/**
 * Clear any scheduled cron jobs
 * Remove any future cron events we scheduled
 */
$cron_jobs = array(
	'ghsales_cleanup_expired_cache',    // Daily cache cleanup
	'ghsales_update_product_stats',     // Daily stats update
	'ghsales_reset_weekly_counters',    // Weekly view counter reset
	'ghsales_reset_monthly_counters',   // Monthly view counter reset
	'ghsales_archive_old_events',       // Monthly event archival
);

foreach ( $cron_jobs as $job ) {
	$timestamp = wp_next_scheduled( $job );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $job );
	}
}

/**
 * Remove custom capabilities (if we added any)
 * Example: manage_ghsales_events, view_ghsales_analytics
 */
// Will be implemented if we add custom capabilities in future

/**
 * Clear WooCommerce caches
 * Force WooCommerce to recalculate prices without our discounts
 */
if ( function_exists( 'wc_delete_product_transients' ) ) {
	// This will be expensive but necessary to clean up cached prices
	$product_ids = $wpdb->get_col(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('product', 'product_variation')"
	);

	foreach ( $product_ids as $product_id ) {
		wc_delete_product_transients( $product_id );
	}
}

/**
 * Restore original Elementor colors if they were backed up
 * This ensures site doesn't keep sale event colors after uninstall
 */
$original_colors = get_option( 'ghsales_original_colors', array() );
if ( ! empty( $original_colors ) && class_exists( '\Elementor\Plugin' ) ) {
	// Restore Elementor color scheme
	$elementor_colors = array(
		1 => $original_colors['primary'],
		2 => $original_colors['secondary'],
		3 => $original_colors['accent'],
		4 => $original_colors['text'],
	);

	update_option( 'elementor_scheme_color', $elementor_colors );

	// Clear Elementor cache
	if ( method_exists( '\Elementor\Plugin', 'instance' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}
}

/**
 * Final cleanup log
 */
error_log( 'GHSales: Complete uninstall cleanup finished - all data removed' );

/**
 * Note to developers:
 *
 * This uninstall script is DESTRUCTIVE and PERMANENT.
 * All plugin data will be deleted with no way to recover.
 *
 * Users should be warned clearly in the settings page:
 * "If you enable this option, all sale events, analytics data,
 *  color schemes, and user tracking data will be permanently
 *  deleted when you uninstall the plugin."
 *
 * Default is to KEEP data (safe option).
 */
