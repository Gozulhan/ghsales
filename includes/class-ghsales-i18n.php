<?php
/**
 * Internationalization (i18n) Helper Class
 *
 * Handles loading and managing translations for GHSales
 * Supports multiple languages via JSON files
 *
 * @package GHSales
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GHSales_i18n Class
 *
 * Manages translations and language switching for sale badges and messages
 */
class GHSales_i18n {

	/**
	 * Available languages
	 *
	 * @var array
	 */
	private static $languages = array(
		'nl_NL' => 'Nederlands',      // Dutch (Default)
		'en_US' => 'English',          // English
		'tr_TR' => 'Türkçe',          // Turkish
		'ar'    => 'العربية',          // Arabic
		'pl_PL' => 'Polski',           // Polish
		'hu_HU' => 'Magyar',           // Hungarian
		'bg_BG' => 'Български',        // Bulgarian
		'uk_UA' => 'Українська',       // Ukrainian
		'de_DE' => 'Deutsch',          // German
		'fr_FR' => 'Français',         // French
		'es_ES' => 'Español',          // Spanish
	);

	/**
	 * Current translations cache
	 *
	 * @var array
	 */
	private static $translations = null;

	/**
	 * Get current locale
	 *
	 * @return string Current WordPress locale (e.g., 'nl_NL')
	 */
	public static function get_current_locale() {
		// Get WordPress locale
		$locale = get_locale();

		// Fallback to Dutch if locale not supported
		if ( ! isset( self::$languages[ $locale ] ) ) {
			$locale = 'nl_NL'; // Default to Dutch
		}

		return $locale;
	}

	/**
	 * Load translations for current locale
	 *
	 * @return array Translations array
	 */
	public static function load_translations() {
		// Return cached translations if already loaded
		if ( self::$translations !== null ) {
			return self::$translations;
		}

		$locale = self::get_current_locale();
		$file_path = GHSALES_PLUGIN_DIR . "languages/{$locale}.json";

		// Load JSON file
		if ( file_exists( $file_path ) ) {
			$json = file_get_contents( $file_path );
			self::$translations = json_decode( $json, true );
		} else {
			// Fallback to Dutch (default)
			$default_file = GHSALES_PLUGIN_DIR . 'languages/nl_NL.json';
			if ( file_exists( $default_file ) ) {
				$json = file_get_contents( $default_file );
				self::$translations = json_decode( $json, true );
			} else {
				// Ultimate fallback to empty array
				self::$translations = array();
			}
		}

		return self::$translations;
	}

	/**
	 * Get translated string
	 *
	 * @param string $key Translation key
	 * @param string $default Default text if translation not found
	 * @return string Translated text
	 */
	public static function get( $key, $default = '' ) {
		$translations = self::load_translations();

		return isset( $translations[ $key ] ) ? $translations[ $key ] : $default;
	}

	/**
	 * Echo translated string
	 *
	 * @param string $key Translation key
	 * @param string $default Default text if translation not found
	 */
	public static function e( $key, $default = '' ) {
		echo esc_html( self::get( $key, $default ) );
	}

	/**
	 * Get all available languages
	 *
	 * @return array Languages array
	 */
	public static function get_languages() {
		return self::$languages;
	}

	/**
	 * Enqueue translations to JavaScript
	 * Makes translations available in frontend JS
	 */
	public static function enqueue_js_translations() {
		$translations = self::load_translations();

		wp_localize_script( 'ghsales-frontend', 'ghsalesTranslations', $translations );
	}
}
