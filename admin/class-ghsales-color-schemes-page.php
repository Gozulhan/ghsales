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
	 * Detect Elementor global colors
	 *
	 * @return array Colors array
	 */
	public static function detect_elementor_colors() {
		$colors = array(
			'primary'   => '#000000',
			'secondary' => '#000000',
			'accent'    => '#000000',
			'text'      => '#000000',
		);

		// Get Elementor Kit ID
		$kit_id = get_option( 'elementor_active_kit' );

		if ( ! $kit_id ) {
			return $colors;
		}

		// Get Kit settings
		$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( empty( $kit_settings ) || ! isset( $kit_settings['system_colors'] ) ) {
			return $colors;
		}

		// Extract system colors
		foreach ( $kit_settings['system_colors'] as $color_item ) {
			$color_id = $color_item['_id'];

			if ( isset( $colors[ $color_id ] ) ) {
				$colors[ $color_id ] = $color_item['color'];
			}
		}

		return $colors;
	}

	/**
	 * AJAX: Save color scheme (create or update)
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
		$scheme_id        = isset( $_POST['scheme_id'] ) ? intval( $_POST['scheme_id'] ) : 0;
		$scheme_name      = isset( $_POST['scheme_name'] ) ? sanitize_text_field( $_POST['scheme_name'] ) : '';
		$primary_color    = isset( $_POST['primary_color'] ) ? sanitize_hex_color( $_POST['primary_color'] ) : '';
		$secondary_color  = isset( $_POST['secondary_color'] ) ? sanitize_hex_color( $_POST['secondary_color'] ) : '';
		$accent_color     = isset( $_POST['accent_color'] ) ? sanitize_hex_color( $_POST['accent_color'] ) : '';
		$text_color       = isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '';
		$background_color = isset( $_POST['background_color'] ) ? sanitize_hex_color( $_POST['background_color'] ) : '#ffffff';

		// Validation
		if ( empty( $scheme_name ) ) {
			wp_send_json_error( 'Scheme name is required' );
		}

		if ( empty( $primary_color ) || empty( $secondary_color ) || empty( $accent_color ) || empty( $text_color ) ) {
			wp_send_json_error( 'All color fields are required' );
		}

		$data = array(
			'scheme_name'      => $scheme_name,
			'primary_color'    => $primary_color,
			'secondary_color'  => $secondary_color,
			'accent_color'     => $accent_color,
			'text_color'       => $text_color,
			'background_color' => $background_color,
		);

		if ( $scheme_id > 0 ) {
			// Update existing scheme
			$result = $wpdb->update(
				$wpdb->prefix . 'ghsales_color_schemes',
				$data,
				array( 'id' => $scheme_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $result ) {
				wp_send_json_error( 'Failed to update color scheme' );
			}

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
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				wp_send_json_error( 'Failed to create color scheme' );
			}

			wp_send_json_success( array(
				'message'   => 'Color scheme created successfully',
				'scheme_id' => $wpdb->insert_id,
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
