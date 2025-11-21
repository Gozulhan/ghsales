<?php
/**
 * GHSales Color Scheme Manager
 *
 * Handles automatic frontend color scheme override during active sales.
 * Injects CSS variables into wp_head to override Elementor global colors.
 *
 * @package GHSales
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color Scheme Manager Class
 *
 * Manages automatic color scheme activation/deactivation based on active sale events.
 * Uses WordPress transient caching (1-hour TTL) to minimize database queries.
 *
 * @since 1.1.0
 */
class GHSales_Color_Scheme {

	/**
	 * Singleton instance
	 *
	 * @var GHSales_Color_Scheme|null
	 */
	private static $instance = null;

	/**
	 * Cache key for active color scheme transient
	 *
	 * @var string
	 */
	const CACHE_KEY = 'ghsales_active_color_scheme';

	/**
	 * Cache duration (1 hour)
	 *
	 * @var int
	 */
	const CACHE_DURATION = HOUR_IN_SECONDS;

	/**
	 * Get singleton instance
	 *
	 * @return GHSales_Color_Scheme
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize color scheme manager
	 * Registers WordPress hooks for CSS injection and cache management
	 *
	 * @return void
	 */
	public static function init() {
		$instance = self::instance();

		// Inject color CSS into frontend head (high priority for specificity)
		add_action( 'wp_head', array( $instance, 'inject_colors' ), 999 );

		// Clear cache when sale events are saved or updated
		add_action( 'save_post_ghsales_event', array( __CLASS__, 'clear_cache' ) );
		add_action( 'transition_post_status', array( __CLASS__, 'clear_cache_on_status_change' ), 10, 3 );

		// Clear cache when color schemes are modified (admin actions)
		add_action( 'ghsales_color_scheme_updated', array( __CLASS__, 'clear_cache' ) );

		// Debug endpoint: add ?ghsales_debug=colors to any frontend URL to see status
		add_action( 'wp_head', array( $instance, 'debug_output' ), 1 );

		// Admin AJAX for cache clear
		add_action( 'wp_ajax_ghsales_clear_color_cache', array( __CLASS__, 'ajax_clear_cache' ) );
	}

	/**
	 * Debug output for color scheme status
	 * Add ?ghsales_debug=colors to any frontend URL to see what's happening
	 *
	 * @return void
	 */
	public function debug_output() {
		// Only show debug if query param is set and user is admin
		if ( ! isset( $_GET['ghsales_debug'] ) || 'colors' !== $_GET['ghsales_debug'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Get cached value directly
		$cached = get_transient( self::CACHE_KEY );
		$fresh  = $this->query_active_color_scheme();

		echo "<!-- GHSALES COLOR DEBUG\n";
		echo "Cache status: " . ( false === $cached ? 'MISS (no cache)' : ( 'none' === $cached ? 'HIT (no active sale cached)' : 'HIT (scheme cached)' ) ) . "\n";
		echo "Fresh query result: " . ( $fresh ? 'Found scheme ID ' . $fresh->id . ' (' . $fresh->scheme_name . ')' : 'No active sale with color scheme' ) . "\n";
		if ( $fresh ) {
			echo "Event: " . $fresh->event_title . " (ID: " . $fresh->event_id . ")\n";
			echo "Colors JSON: " . ( ! empty( $fresh->colors_json ) ? 'Yes (' . strlen( $fresh->colors_json ) . ' chars)' : 'Empty' ) . "\n";
			echo "Legacy colors:\n";
			echo "  primary_color: " . $fresh->primary_color . " (" . ( $this->validate_hex_color( $fresh->primary_color ) ? 'VALID' : 'INVALID' ) . ")\n";
			echo "  secondary_color: " . $fresh->secondary_color . " (" . ( $this->validate_hex_color( $fresh->secondary_color ) ? 'VALID' : 'INVALID' ) . ")\n";
			echo "  accent_color: " . $fresh->accent_color . " (" . ( $this->validate_hex_color( $fresh->accent_color ) ? 'VALID' : 'INVALID' ) . ")\n";
			echo "  text_color: " . $fresh->text_color . " (" . ( $this->validate_hex_color( $fresh->text_color ) ? 'VALID' : 'INVALID' ) . ")\n";
			echo "Validation: " . ( $this->validate_color_scheme( $fresh ) ? 'PASSED' : 'FAILED' ) . "\n";
		}
		echo "-->\n";
	}

	/**
	 * AJAX handler to clear color cache from admin
	 *
	 * @return void
	 */
	public static function ajax_clear_cache() {
		check_ajax_referer( 'ghsales_color_schemes', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		self::clear_cache();
		wp_send_json_success( array( 'message' => 'Color cache cleared successfully' ) );
	}

	/**
	 * Inject color override CSS into wp_head
	 *
	 * Main method that runs on every frontend page load.
	 * Gets active sale color scheme from cache and injects CSS variables.
	 *
	 * @return void
	 */
	public function inject_colors() {
		// Skip if in admin area
		if ( is_admin() ) {
			return;
		}

		// Skip if in Elementor editor preview
		if ( isset( $_GET['elementor-preview'] ) ) {
			return;
		}

		// Get active color scheme (cached)
		$colors = $this->get_cached_active_colors();

		// No active sale with color scheme
		if ( ! $colors ) {
			return;
		}

		// Validate all colors before injection
		if ( ! $this->validate_color_scheme( $colors ) ) {
			error_log( 'GHSales: Invalid color scheme detected, skipping injection. Scheme ID: ' . $colors->id );
			return;
		}

		// Inject CSS variables
		$this->output_color_css( $colors );

		// Log for debugging (remove in production if needed)
		error_log( 'GHSales: Color scheme injected - Scheme ID: ' . $colors->id . ', Primary: ' . $colors->primary_color );
	}

	/**
	 * Output color override CSS
	 *
	 * Generates and echoes CSS with Elementor global color variable overrides.
	 * Supports DYNAMIC colors from colors_json (new) OR legacy columns (backward compatible).
	 * Uses !important to ensure override of theme/plugin defaults.
	 *
	 * @param object $colors Color scheme object from database
	 * @return void
	 */
	private function output_color_css( $colors ) {
		// Decode colors from JSON if available (new format)
		$all_colors = array();
		if ( ! empty( $colors->colors_json ) ) {
			$all_colors = json_decode( $colors->colors_json, true );
		}

		// Fallback to legacy columns if JSON not available (backward compatibility)
		if ( empty( $all_colors ) ) {
			$all_colors = array(
				'primary'    => $colors->primary_color,
				'secondary'  => $colors->secondary_color,
				'accent'     => $colors->accent_color,
				'text'       => $colors->text_color,
				'background' => isset( $colors->background_color ) ? $colors->background_color : '#ffffff',
			);
		}

		// Filter out any invalid colors
		$all_colors = array_filter( $all_colors, function( $color ) {
			return ! empty( $color ) && preg_match( '/^#[a-fA-F0-9]{6}$/', $color );
		});

		if ( empty( $all_colors ) ) {
			error_log( 'GHSales: No valid colors found in scheme ID: ' . $colors->id );
			return;
		}

		?>
<style id="ghsales-color-override">
:root {
	<?php
	// Output ALL colors as Elementor global color variables
	foreach ( $all_colors as $color_id => $color_hex ) :
		$sanitized_id = sanitize_key( $color_id );
		?>
	/* <?php echo esc_html( ucfirst( $color_id ) ); ?> Color */
	--e-global-color-<?php echo esc_attr( $sanitized_id ); ?>: <?php echo esc_attr( $color_hex ); ?> !important;
	--<?php echo esc_attr( $sanitized_id ); ?>-color: <?php echo esc_attr( $color_hex ); ?> !important;
	--<?php echo esc_attr( $sanitized_id ); ?>: <?php echo esc_attr( $color_hex ); ?> !important;
	<?php endforeach; ?>
}

/* High-specificity override for stubborn themes */
body {
	<?php foreach ( $all_colors as $color_id => $color_hex ) : ?>
	--<?php echo esc_attr( sanitize_key( $color_id ) ); ?>: <?php echo esc_attr( $color_hex ); ?> !important;
	<?php endforeach; ?>
}

/* Additional compatibility for Elementor custom colors */
<?php
foreach ( $all_colors as $color_id => $color_hex ) :
	// Only add custom color overrides (not system colors to avoid duplication)
	if ( ! in_array( $color_id, array( 'primary', 'secondary', 'accent', 'text' ), true ) ) :
		?>
.elementor-widget-heading .elementor-heading-title[class*="elementor-color-<?php echo esc_attr( sanitize_key( $color_id ) ); ?>"],
.elementor-widget-button .elementor-button[class*="elementor-color-<?php echo esc_attr( sanitize_key( $color_id ) ); ?>"] {
	color: <?php echo esc_attr( $color_hex ); ?> !important;
}
	<?php
	endif;
endforeach;
?>
</style>
		<?php

		// Log injected colors for debugging
		error_log( 'GHSales: Injected ' . count( $all_colors ) . ' colors for scheme ID: ' . $colors->id );
	}

	/**
	 * Get active color scheme with caching
	 *
	 * Checks transient cache first, queries database if cache miss.
	 * Caches result for CACHE_DURATION (1 hour) to minimize DB queries.
	 *
	 * @return object|null Color scheme object or null if no active sale
	 */
	private function get_cached_active_colors() {
		// Try to get from cache first
		$cached = get_transient( self::CACHE_KEY );

		// Cache hit - return cached value (may be null if no active sale)
		if ( false !== $cached ) {
			// Cache stores 'none' string when no active sale (to avoid repeated queries)
			if ( 'none' === $cached ) {
				return null;
			}
			return $cached;
		}

		// Cache miss - query database
		$colors = $this->query_active_color_scheme();

		// Cache the result
		if ( $colors ) {
			set_transient( self::CACHE_KEY, $colors, self::CACHE_DURATION );
		} else {
			// Cache 'none' to avoid repeated queries when no sale is active
			set_transient( self::CACHE_KEY, 'none', self::CACHE_DURATION );
		}

		return $colors;
	}

	/**
	 * Query database for active sale color scheme
	 *
	 * Finds the currently active sale event with a color scheme assigned.
	 * If multiple sales are active, returns the one with earliest start date (priority).
	 *
	 * @return object|null Color scheme object with all color fields or null
	 */
	private function query_active_color_scheme() {
		global $wpdb;

		// Get current time in MySQL format
		$now = current_time( 'mysql' );

		// Query for active sale with color scheme
		// Joins: posts → start_date → end_date → color_scheme_id → color_schemes table
		// Now includes colors_json for dynamic color support
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					cs.id,
					cs.scheme_name,
					cs.primary_color,
					cs.secondary_color,
					cs.accent_color,
					cs.text_color,
					cs.background_color,
					cs.colors_json,
					p.ID as event_id,
					p.post_title as event_title,
					pm1.meta_value as start_date,
					pm2.meta_value as end_date
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
					AND pm1.meta_key = '_ghsales_start_date'
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
					AND pm2.meta_key = '_ghsales_end_date'
				LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id
					AND pm3.meta_key = '_ghsales_color_scheme_id'
				LEFT JOIN {$wpdb->prefix}ghsales_color_schemes cs
					ON pm3.meta_value = cs.id
				WHERE p.post_type = 'ghsales_event'
					AND p.post_status = 'publish'
					AND pm1.meta_value <= %s
					AND pm2.meta_value >= %s
					AND pm3.meta_value IS NOT NULL
					AND pm3.meta_value != ''
					AND cs.id IS NOT NULL
				ORDER BY pm1.meta_value ASC
				LIMIT 1",
				$now,
				$now
			)
		);

		// Log query result for debugging
		if ( $result ) {
			error_log( 'GHSales: Active color scheme found - Event: ' . $result->event_title . ' (ID: ' . $result->event_id . ')' );
		} else {
			error_log( 'GHSales: No active sale with color scheme found' );
		}

		return $result;
	}

	/**
	 * Validate color scheme object
	 *
	 * Ensures all required color fields exist and contain valid hex colors.
	 * Prevents CSS injection of invalid/malicious values.
	 *
	 * @param object $colors Color scheme object to validate
	 * @return bool True if valid, false otherwise
	 */
	private function validate_color_scheme( $colors ) {
		// Check object exists
		if ( ! is_object( $colors ) ) {
			return false;
		}

		// Required color fields
		$required_fields = array( 'primary_color', 'secondary_color', 'accent_color', 'text_color' );

		// Validate each field exists and is valid hex color
		foreach ( $required_fields as $field ) {
			if ( ! isset( $colors->$field ) || ! $this->validate_hex_color( $colors->$field ) ) {
				error_log( 'GHSales: Invalid or missing color field: ' . $field );
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate hex color format
	 *
	 * Checks if string is valid hex color (#RGB or #RRGGBB format).
	 *
	 * @param string $color Color string to validate
	 * @return bool True if valid hex color, false otherwise
	 */
	private function validate_hex_color( $color ) {
		// Check format: # followed by 3 or 6 hex digits
		return (bool) preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color );
	}

	/**
	 * Clear color scheme cache
	 *
	 * Called when sale events are saved/updated to ensure fresh data.
	 * Public static method so it can be called from hooks.
	 *
	 * @return void
	 */
	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
		error_log( 'GHSales: Color scheme cache cleared' );
	}

	/**
	 * Clear cache on post status change
	 *
	 * Handles transitions like draft→publish, publish→draft, etc.
	 * Only clears cache for ghsales_event post type.
	 *
	 * @param string  $new_status New post status
	 * @param string  $old_status Old post status
	 * @param WP_Post $post       Post object
	 * @return void
	 */
	public static function clear_cache_on_status_change( $new_status, $old_status, $post ) {
		// Only care about ghsales_event post type
		if ( 'ghsales_event' !== $post->post_type ) {
			return;
		}

		// Clear cache if status changed to/from publish
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			self::clear_cache();
		}
	}

	/**
	 * Get current active color scheme (public method for admin/debugging)
	 *
	 * Returns the currently active color scheme without caching.
	 * Useful for admin displays and debugging.
	 *
	 * @return object|null Color scheme object or null
	 */
	public static function get_current_active_scheme() {
		$instance = self::instance();
		return $instance->query_active_color_scheme();
	}

	/**
	 * Force refresh color scheme cache
	 *
	 * Clears cache and immediately queries for active scheme.
	 * Returns the fresh color scheme data.
	 *
	 * @return object|null Fresh color scheme object or null
	 */
	public static function refresh_cache() {
		self::clear_cache();
		$instance = self::instance();
		return $instance->get_cached_active_colors();
	}
}
