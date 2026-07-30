<?php
/**
 * File-name constants for all mo-otp ability classes.
 *
 * Each constant below holds the file name of one ability class. They are
 * used in class-moabilitiesapi.php for require_once calls. Keeping the
 * names here means a file rename only needs to be updated in one place.
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * File names for every mo-otp ability class, plus shared premium-gate helpers.
 */
class MoAbilitiesConstants {

	/** Send and verify OTP tokens via SMS, Email, and WhatsApp. */
	const OTP_ACTIONS_FILE = 'class-mootpactionsabilities.php';

	/** Read and update OTP length, validity, and phone/domain block lists. */
	const OTP_SETTINGS_FILE = 'class-mootpsettingsabilities.php';

	/** List, configure, enable, and disable all supported form integrations. */
	const FORM_MANAGEMENT_FILE = 'class-moformmanagementabilities.php';

	/** Read and update the WordPress / WooCommerce / UM login form OTP settings. */
	const LOGIN_FORM_FILE = 'class-mologinformabilities.php';

	/** Check plugin health and list premium (paid-plan) form integrations. */
	const DIAGNOSTICS_FILE = 'class-modiagnosticsabilities.php';

	/** Read and update OTP popup templates and user-facing message text. */
	const CUSTOMIZATION_FILE = 'class-mocustomizationabilities.php';

	/** Read and update SMS/Email gateway, WhatsApp, and OTP message templates. */
	const GATEWAY_CONFIG_FILE = 'class-mogatewayconfigabilities.php';

	/** Enable logging and read, export, or clear OTP transaction logs. */
	const REPORTING_FILE = 'class-moreportingabilities.php';

	/** Manage the linked miniOrange account, license key, support, and feedback. */
	const ACCOUNT_FILE = 'class-moaccountabilities.php';

	/** Configure country restrictions, rate limits, SMS notifications, and add-ons. */
	const ADDONS_SETTINGS_FILE = 'class-moaddonssettingsabilities.php';

	/**
	 * Returns a standardized response indicating a feature requires a premium plan.
	 *
	 * @return array Response array with success=false, premium_required=true, and user-facing message.
	 */
	public static function premium_required_response() {
		return array(
			'success'          => false,
			'premium_required' => true,
			'message'          => 'This is a premium feature. Please upgrade your plan.',
		);
	}

	/**
	 * Registers an ability, but only when the WordPress Abilities API is available.
	 *
	 * The Abilities API (wp_register_ability()) ships with WordPress 6.9.0. This
	 * plugin supports much older versions, so every ability registration is routed
	 * through this guarded wrapper. On older versions the wp_abilities_api_init hook
	 * never fires anyway, so this is purely defensive and keeps the plugin compatible
	 * with its declared minimum supported WordPress version.
	 *
	 * @param string $ability_id The unique ability identifier (e.g. 'mo-otp/send-otp').
	 * @param array  $args       The ability arguments passed to wp_register_ability().
	 *
	 * @return void
	 */
	public static function register_ability( $ability_id, array $args ) {
		if ( function_exists( 'wp_register_ability' ) ) {
			wp_register_ability( $ability_id, $args );
		}
	}

	/**
	 * Registers an ability category, but only when the WordPress Abilities API is available.
	 *
	 * wp_register_ability_category() ships with WordPress 6.9.0. See register_ability()
	 * for why this is routed through a guarded wrapper.
	 *
	 * @param string $category_slug The unique category slug (e.g. 'mo-otp').
	 * @param array  $args          The category arguments passed to wp_register_ability_category().
	 *
	 * @return void
	 */
	public static function register_ability_category( $category_slug, array $args ) {
		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category( $category_slug, $args );
		}
	}

	/**
	 * Sanitizes a multi-line string, falling back gracefully on older WordPress.
	 *
	 * sanitize_textarea_field() was introduced in WordPress 4.7.0. On earlier
	 * versions this falls back to sanitize_text_field() so the plugin stays
	 * compatible with its declared minimum supported WordPress version.
	 *
	 * @param string $value The raw string to sanitize.
	 *
	 * @return string The sanitized string.
	 */
	public static function sanitize_textarea( $value ) {
		if ( function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( $value );
		}
		return sanitize_text_field( $value );
	}
}
