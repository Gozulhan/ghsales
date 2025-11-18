<?php
/**
 * GHSales GDPR Helper Class
 *
 * Handles GDPR compliance and consent checking.
 *
 * TRACKING LOGIC:
 * - DEFAULT: Always track (no GDPR plugin = track everything)
 * - EXCEPTION: Only stop if Cookiebot/CookieYes explicitly denies consent
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_GDPR Class
 *
 * Manages consent checking and privacy utilities
 */
class GHSales_GDPR {

	/**
	 * Detected GDPR plugin (cached)
	 *
	 * @var string|null
	 */
	private static $gdpr_plugin = null;

	/**
	 * Check if we should track user activity
	 *
	 * DEFAULT: Returns TRUE (always track)
	 * EXCEPTION: Returns FALSE only if GDPR plugin explicitly denies consent
	 *
	 * @return bool True if tracking is allowed, false otherwise
	 */
	public static function should_track() {
		// Detect GDPR plugin (cache result)
		if ( is_null( self::$gdpr_plugin ) ) {
			self::$gdpr_plugin = self::detect_gdpr_plugin();
		}

		// No GDPR plugin detected → DEFAULT: Track everything
		if ( ! self::$gdpr_plugin ) {
			return true;
		}

		// GDPR plugin detected → Check consent status
		switch ( self::$gdpr_plugin ) {
			case 'cookiebot':
				return self::check_cookiebot_consent();

			case 'cookieyes':
				return self::check_cookieyes_consent();

			case 'complianz':
				return self::check_complianz_consent();

			case 'moove_gdpr':
				return self::check_moove_gdpr_consent();

			default:
				// Unknown GDPR plugin → DEFAULT: Track
				return true;
		}
	}

	/**
	 * Detect active GDPR/cookie consent plugin
	 *
	 * @return string|null Plugin identifier or null if none detected
	 */
	private static function detect_gdpr_plugin() {
		// Check for Cookiebot
		if ( class_exists( 'Cookiebot_WP' ) || is_plugin_active( 'cookiebot/cookiebot.php' ) ) {
			return 'cookiebot';
		}

		// Check for CookieYes (GDPR Cookie Consent)
		if ( class_exists( 'CookieYes' ) || is_plugin_active( 'cookie-law-info/cookie-law-info.php' ) ) {
			return 'cookieyes';
		}

		// Check for Complianz
		if ( class_exists( 'COMPLIANZ' ) || is_plugin_active( 'complianz-gdpr/complianz-gdpr.php' ) ) {
			return 'complianz';
		}

		// Check for GDPR Cookie Compliance (Moove)
		if ( is_plugin_active( 'gdpr-cookie-compliance/moove-gdpr.php' ) ) {
			return 'moove_gdpr';
		}

		// No GDPR plugin detected
		return null;
	}

	/**
	 * Check Cookiebot consent status
	 *
	 * @return bool True if consent given or no choice made yet, false if explicitly denied
	 */
	private static function check_cookiebot_consent() {
		// No cookie set yet → DEFAULT: Track (user hasn't made choice)
		if ( ! isset( $_COOKIE['CookieConsent'] ) ) {
			return true;
		}

		// Decode consent cookie
		$consent = json_decode( stripslashes( $_COOKIE['CookieConsent'] ), true );

		// Check 'statistics' consent (analytics category)
		// If not set → DEFAULT: Track
		// If set to false → Don't track
		if ( ! isset( $consent['statistics'] ) ) {
			return true; // No choice made = track
		}

		return ! empty( $consent['statistics'] );
	}

	/**
	 * Check CookieYes consent status
	 *
	 * @return bool True if consent given or no choice made yet, false if explicitly denied
	 */
	private static function check_cookieyes_consent() {
		// No cookie set yet → DEFAULT: Track
		if ( ! isset( $_COOKIE['cookieyes-consent'] ) ) {
			return true;
		}

		$consent = $_COOKIE['cookieyes-consent'];

		// Check if analytics category is accepted
		// Format: "consent:yes,analytics:yes" or "consent:yes,analytics:no"
		if ( strpos( $consent, 'analytics:no' ) !== false ) {
			return false; // Explicitly denied
		}

		// Default or accepted → Track
		return true;
	}

	/**
	 * Check Complianz consent status
	 *
	 * @return bool True if consent given or no choice made yet, false if explicitly denied
	 */
	private static function check_complianz_consent() {
		// No cookie set yet → DEFAULT: Track
		if ( ! isset( $_COOKIE['complianz_consent_status'] ) ) {
			return true;
		}

		$consent = json_decode( stripslashes( $_COOKIE['complianz_consent_status'] ), true );

		// Check 'statistics' consent
		if ( ! isset( $consent['statistics'] ) ) {
			return true; // No choice = track
		}

		// 'deny' = don't track, anything else = track
		return $consent['statistics'] !== 'deny';
	}

	/**
	 * Check Moove GDPR consent status
	 *
	 * @return bool True if consent given or no choice made yet, false if explicitly denied
	 */
	private static function check_moove_gdpr_consent() {
		// No cookie set yet → DEFAULT: Track
		if ( ! isset( $_COOKIE['moove_gdpr_popup'] ) ) {
			return true;
		}

		$consent = json_decode( stripslashes( $_COOKIE['moove_gdpr_popup'] ), true );

		// Check for third-party/analytics consent
		if ( ! isset( $consent['thirdparty'] ) ) {
			return true; // No choice = track
		}

		return ! empty( $consent['thirdparty'] );
	}

	/**
	 * Get current session ID
	 * Uses WooCommerce session if available, creates fallback
	 *
	 * @return string Session identifier
	 */
	public static function get_session_id() {
		// Try WooCommerce session first
		if ( function_exists( 'WC' ) && WC()->session ) {
			$customer = WC()->session->get_customer_id();
			if ( $customer ) {
				return 'wc_' . $customer;
			}
		}

		// Fallback: Use PHP session
		if ( ! session_id() ) {
			session_start();
		}

		return 'php_' . session_id();
	}

	/**
	 * Get current user ID
	 * Returns WordPress user ID if logged in, null otherwise
	 *
	 * @return int|null User ID or null for guests
	 */
	public static function get_user_id() {
		return is_user_logged_in() ? get_current_user_id() : null;
	}

	/**
	 * Mask IP address for GDPR compliance
	 *
	 * Data minimization principle:
	 * - IPv4: Remove last octet (192.168.1.1 → 192.168.1.0)
	 * - IPv6: Remove last 80 bits (keep first 48 bits)
	 *
	 * @param string $ip IP address to mask
	 * @return string Masked IP address
	 */
	public static function mask_ip( $ip ) {
		// Validate IP
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0'; // Invalid IP
		}

		// IPv4
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts = explode( '.', $ip );
			$parts[3] = '0'; // Remove last octet
			return implode( '.', $parts );
		}

		// IPv6
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// Convert to binary, keep first 48 bits, zero out rest
			$binary = inet_pton( $ip );
			$masked = substr( $binary, 0, 6 ) . str_repeat( chr( 0 ), 10 );
			return inet_ntop( $masked );
		}

		return '0.0.0.0'; // Fallback
	}

	/**
	 * Get visitor's IP address
	 * Checks various headers for proxied requests
	 *
	 * @return string IP address
	 */
	public static function get_ip_address() {
		// Check for proxy headers
		$headers = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_FORWARDED_FOR',  // Standard proxy header
			'HTTP_X_REAL_IP',        // Nginx proxy
			'REMOTE_ADDR',           // Direct connection
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );

				// Handle multiple IPs in X-Forwarded-For (take first one)
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip = trim( $ips[0] );
				}

				// Validate and return
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0'; // Fallback if no valid IP found
	}

	/**
	 * Get visitor's user agent
	 *
	 * @return string User agent string
	 */
	public static function get_user_agent() {
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}

		return 'Unknown';
	}

	/**
	 * Log consent decision to database
	 * (For future use if we build our own consent banner)
	 *
	 * @param string $consent_type Type: 'analytics', 'marketing', 'necessary'
	 * @param bool   $consent_given True if consented, false if rejected
	 * @return void
	 */
	public static function log_consent( $consent_type, $consent_given ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'ghsales_consent_log',
			array(
				'session_id'     => self::get_session_id(),
				'user_id'        => self::get_user_id(),
				'consent_type'   => $consent_type,
				'consent_given'  => $consent_given ? 1 : 0,
				'ip_address'     => self::mask_ip( self::get_ip_address() ),
				'consent_date'   => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Delete all user data (Right to be forgotten - GDPR Article 17)
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool True on success, false on failure
	 */
	public static function delete_user_data( $user_id ) {
		global $wpdb;

		// Delete user activity
		$wpdb->delete(
			$wpdb->prefix . 'ghsales_user_activity',
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		// Delete consent log
		$wpdb->delete(
			$wpdb->prefix . 'ghsales_consent_log',
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		// Delete upsell cache
		$wpdb->delete(
			$wpdb->prefix . 'ghsales_upsell_cache',
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		return true;
	}

	/**
	 * Export all user data (Right to access - GDPR Article 20)
	 *
	 * @param int $user_id WordPress user ID
	 * @return array User data for export
	 */
	public static function export_user_data( $user_id ) {
		global $wpdb;

		$data = array(
			'user_activity' => array(),
			'consent_log'   => array(),
		);

		// Export user activity
		$activity = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ghsales_user_activity WHERE user_id = %d ORDER BY timestamp DESC",
				$user_id
			),
			ARRAY_A
		);

		$data['user_activity'] = $activity;

		// Export consent log
		$consent = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ghsales_consent_log WHERE user_id = %d ORDER BY consent_date DESC",
				$user_id
			),
			ARRAY_A
		);

		$data['consent_log'] = $consent;

		return $data;
	}
}
