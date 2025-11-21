<?php
/**
 * GHSales Color Schemes Manager
 *
 * Admin page for creating, editing, and managing color schemes.
 * Provides color picker interface for custom scheme creation.
 *
 * @package GHSales
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Color Schemes Manager Page Class
 *
 * Handles CRUD operations for color schemes and provides admin UI.
 *
 * @since 1.2.0
 */
class GHSales_Color_Schemes_Page {

	/**
	 * Initialize color schemes page
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu_page' ), 21 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_ghsales_save_color_scheme', array( __CLASS__, 'ajax_save_color_scheme' ) );
		add_action( 'wp_ajax_ghsales_delete_color_scheme', array( __CLASS__, 'ajax_delete_color_scheme' ) );
		add_action( 'wp_ajax_ghsales_get_elementor_colors', array( __CLASS__, 'ajax_get_elementor_colors' ) );
	}

	/**
	 * Register Color Schemes submenu page
	 *
	 * @return void
	 */
	public static function register_menu_page() {
		add_submenu_page(
			'ghsales',
			__( 'Color Schemes', 'ghsales' ),
			__( 'Color Schemes', 'ghsales' ),
			'manage_woocommerce',
			'ghsales-color-schemes',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue CSS and JS assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		// Only load on our color schemes page
		if ( 'gh-sales_page_ghsales-color-schemes' !== $hook ) {
			return;
		}

		// WordPress color picker
		wp_enqueue_style( 'wp-color-picker' );

		// Custom CSS
		wp_enqueue_style(
			'ghsales-color-schemes',
			GHSALES_PLUGIN_URL . 'assets/css/color-schemes-admin.css',
			array( 'wp-color-picker' ),
			GHSALES_VERSION
		);

		// Custom JS
		wp_enqueue_script(
			'ghsales-color-schemes',
			GHSALES_PLUGIN_URL . 'assets/js/color-schemes-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			GHSALES_VERSION,
			true
		);

		// Pass data to JS
		wp_localize_script(
			'ghsales-color-schemes',
			'ghsalesColorSchemes',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ghsales_color_schemes' ),
				'strings' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this color scheme?', 'ghsales' ),
					'saving'        => __( 'Saving...', 'ghsales' ),
					'saved'         => __( 'Color scheme saved!', 'ghsales' ),
					'error'         => __( 'Error saving color scheme.', 'ghsales' ),
					'nameRequired'  => __( 'Please enter a scheme name.', 'ghsales' ),
				),
			)
		);
	}

	/**
	 * Render color schemes manager page
	 *
	 * @return void
	 */
	public static function render_page() {
		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ghsales' ) );
		}

		// Get all color schemes
		$color_schemes = self::get_all_color_schemes();

		// Get Elementor colors for pre-fill
		$elementor_colors = self::detect_elementor_colors();

		// Load template
		include GHSALES_PLUGIN_DIR . 'admin/partials/color-schemes-manager.php';
	}

	/**
	 * Get all color schemes from database
	 *
	 * @return array Color schemes
	 */
	public static function get_all_color_schemes() {
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}ghsales_color_schemes
			ORDER BY id ASC"
		);

		return $results ? $results : array();
	}

	/**
	 * Get single color scheme by ID
	 *
	 * @param int $scheme_id Scheme ID
	 * @return object|null Color scheme object or null
	 */
	public static function get_color_scheme( $scheme_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ghsales_color_schemes
				WHERE id = %d",
				$scheme_id
			)
		);
	}

	/**
	 * Detect ALL Elementor global colors (system + custom)
	 *
	 * @return array All colors with structure: [color_id => ['color' => '#hex', 'title' => 'Name', 'type' => 'system'|'custom']]
	 */
	public static function detect_elementor_colors() {
		$all_colors = array();

		// Get Elementor Kit ID
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return $all_colors;
		}

		// Get Kit settings
		$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( empty( $kit_settings ) ) {
			return $all_colors;
		}

		// Extract SYSTEM colors
		if ( ! empty( $kit_settings['system_colors'] ) ) {
			foreach ( $kit_settings['system_colors'] as $color_item ) {
				if ( isset( $color_item['_id'] ) && isset( $color_item['color'] ) ) {
					$color_id    = $color_item['_id'];
					$hex_color   = $color_item['color'];
					$color_title = isset( $color_item['title'] ) ? $color_item['title'] : ucfirst( $color_id );

					$all_colors[ $color_id ] = array(
						'color' => $hex_color,
						'title' => $color_title,
						'type'  => 'system',
					);
				}
			}
		}

		// Extract CUSTOM colors
		if ( ! empty( $kit_settings['custom_colors'] ) ) {
			foreach ( $kit_settings['custom_colors'] as $color_item ) {
				if ( isset( $color_item['_id'] ) && isset( $color_item['color'] ) ) {
					$color_id    = $color_item['_id'];
					$hex_color   = $color_item['color'];
					$color_title = isset( $color_item['title'] ) ? $color_item['title'] : $color_id;

					$all_colors[ $color_id ] = array(
						'color' => $hex_color,
						'title' => $color_title,
						'type'  => 'custom',
					);
				}
			}
		}

		return $all_colors;
	}

	/**
	 * AJAX: Save color scheme (create or update)
	 * Stores ALL colors as JSON for full flexibility
	 *
	 * @return void
	 */
	public static function ajax_save_color_scheme() {
		// Verify nonce
		check_ajax_referer( 'ghsales_color_schemes', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;

		// Get and sanitize input
		$scheme_id   = isset( $_POST['scheme_id'] ) ? intval( $_POST['scheme_id'] ) : 0;
		$scheme_name = isset( $_POST['scheme_name'] ) ? sanitize_text_field( $_POST['scheme_name'] ) : '';
		$colors_data = isset( $_POST['colors'] ) ? $_POST['colors'] : array();

		// Validation
		if ( empty( $scheme_name ) ) {
			wp_send_json_error( 'Scheme name is required' );
		}

		if ( empty( $colors_data ) || ! is_array( $colors_data ) ) {
			wp_send_json_error( 'No colors provided' );
		}

		// Sanitize all colors
		$sanitized_colors = array();
		foreach ( $colors_data as $color_id => $color_value ) {
			$sanitized_hex = sanitize_hex_color( $color_value );
			if ( $sanitized_hex ) {
				$sanitized_colors[ sanitize_key( $color_id ) ] = $sanitized_hex;
			}
		}

		if ( empty( $sanitized_colors ) ) {
			wp_send_json_error( 'No valid colors provided' );
		}

		// Prepare data array with backward compatibility for basic 5 colors
		$data = array(
			'scheme_name'      => $scheme_name,
			'primary_color'    => isset( $sanitized_colors['primary'] ) ? $sanitized_colors['primary'] : '#000000',
			'secondary_color'  => isset( $sanitized_colors['secondary'] ) ? $sanitized_colors['secondary'] : '#000000',
			'accent_color'     => isset( $sanitized_colors['accent'] ) ? $sanitized_colors['accent'] : '#000000',
			'text_color'       => isset( $sanitized_colors['text'] ) ? $sanitized_colors['text'] : '#000000',
			'background_color' => isset( $sanitized_colors['background'] ) ? $sanitized_colors['background'] : '#ffffff',
			'colors_json'      => wp_json_encode( $sanitized_colors ), // Store ALL colors as JSON
		);

		if ( $scheme_id > 0 ) {
			// Update existing scheme
			$result = $wpdb->update(
				$wpdb->prefix . 'ghsales_color_schemes',
				$data,
				array( 'id' => $scheme_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				wp_send_json_error( 'Failed to update color scheme' );
			}

			// Clear color cache so changes take effect immediately
			do_action( 'ghsales_color_scheme_updated', $scheme_id );

			wp_send_json_success( array(
				'message'   => 'Color scheme updated successfully',
				'scheme_id' => $scheme_id,
			) );

		} else {
			// Create new scheme
			$data['created_at'] = current_time( 'mysql' );

			$result = $wpdb->insert(
				$wpdb->prefix . 'ghsales_color_schemes',
				$data,
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				wp_send_json_error( 'Failed to create color scheme' );
			}

			$new_scheme_id = $wpdb->insert_id;

			// Clear color cache so new scheme is available immediately
			do_action( 'ghsales_color_scheme_updated', $new_scheme_id );

			wp_send_json_success( array(
				'message'   => 'Color scheme created successfully',
				'scheme_id' => $new_scheme_id,
			) );
		}
	}

	/**
	 * AJAX: Delete color scheme
	 *
	 * @return void
	 */
	public static function ajax_delete_color_scheme() {
		// Verify nonce
		check_ajax_referer( 'ghsales_color_schemes', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;

		$scheme_id = isset( $_POST['scheme_id'] ) ? intval( $_POST['scheme_id'] ) : 0;

		if ( $scheme_id <= 0 ) {
			wp_send_json_error( 'Invalid scheme ID' );
		}

		// Prevent deleting default scheme (ID 1)
		if ( 1 === $scheme_id ) {
			wp_send_json_error( 'Cannot delete default color scheme' );
		}

		$result = $wpdb->delete(
			$wpdb->prefix . 'ghsales_color_schemes',
			array( 'id' => $scheme_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( 'Failed to delete color scheme' );
		}

		wp_send_json_success( 'Color scheme deleted successfully' );
	}

	/**
	 * AJAX: Get Elementor colors for pre-fill
	 *
	 * @return void
	 */
	public static function ajax_get_elementor_colors() {
		// Verify nonce
		check_ajax_referer( 'ghsales_color_schemes', 'nonce' );

		// Check permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$colors = self::detect_elementor_colors();

		wp_send_json_success( $colors );
	}
}
