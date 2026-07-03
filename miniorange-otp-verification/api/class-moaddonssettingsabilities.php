<?php
/**
 * Addon and plugin settings abilities.
 *
 * Registers:
 *   mo-otp/get-general-settings
 *   mo-otp/update-general-settings
 *   mo-otp/get-country-restrictions
 *   mo-otp/update-country-restrictions
 *   mo-otp/get-rate-limit-settings
 *   mo-otp/update-rate-limit-settings
 *   mo-otp/get-sms-notifications-settings
 *   mo-otp/update-sms-notifications-settings
 *   mo-otp/list-available-addons
 *   mo-otp/get-transaction-cost-estimate
 *   mo-otp/get-form-field-mappings
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\AddOnList;

/**
 * Registers addon and plugin settings abilities.
 */
class MoAddonsSettingsAbilities {

	/**
	 * WooCommerce and Ultimate Member notification event key => option prefix map.
	 * Shared between get and update handlers.
	 */
	private static function notification_event_map() {
		return array(
			'new_order'        => 'mo_wc_notif_new_order',
			'order_processing' => 'mo_wc_notif_processing',
			'order_completed'  => 'mo_wc_notif_completed',
			'order_cancelled'  => 'mo_wc_notif_cancelled',
			'order_refunded'   => 'mo_wc_notif_refunded',
			'order_on_hold'    => 'mo_wc_notif_on_hold',
			'order_failed'     => 'mo_wc_notif_failed',
			'notify_admin'     => 'mo_wc_notif_admin',
			'user_registered'  => 'mo_um_notif_registered',
			'user_approved'    => 'mo_um_notif_approved',
			'user_rejected'    => 'mo_um_notif_rejected',
		);
	}

	/**
	 * Registers all addon and plugin settings abilities with the WordPress Abilities API.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_general_settings();
		static::register_update_general_settings();
		static::register_get_country_restrictions();
		static::register_update_country_restrictions();
		static::register_get_rate_limit_settings();
		static::register_update_rate_limit_settings();
		static::register_get_sms_notifications_settings();
		static::register_update_sms_notifications_settings();
		static::register_list_available_addons();
		static::register_get_transaction_cost_estimate();
		static::register_get_form_field_mappings();
	}

	/**
	 * Registers the mo-otp/get-general-settings ability.
	 *
	 * Returns general plugin behavior options such as whether to show remaining
	 * transaction counts, country-code dropdown visibility, duplicate phone
	 * handling, the default country code, and VoIP blocking.
	 *
	 * @return void
	 */
	public static function register_get_general_settings() {
		wp_register_ability(
			'mo-otp/get-general-settings',
			array(
				'label'               => 'Get General Settings',
				'description'         => 'Retrieve general plugin behavior settings: remaining-transaction display, country code dropdown, duplicate phone handling, default country code, and VoIP blocking.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'show_remaining_transactions' => array( 'type' => 'boolean', 'description' => 'Show a remaining-transaction counter in the admin.' ),
						'show_dropdown_on_form'       => array( 'type' => 'boolean', 'description' => 'Show a country-code dropdown on supported forms.' ),
						'allow_duplicate_phone'       => array( 'type' => 'boolean', 'description' => 'Allow multiple accounts to share the same phone number.' ),
						'default_country_code'        => array( 'type' => array( 'string', 'null' ), 'description' => 'Default country code prepended to phone numbers (e.g. +1).' ),
						'remove_plus_sign'            => array( 'type' => 'boolean', 'description' => 'Strip the leading + from phone numbers before sending SMS.' ),
						'check_voip'                  => array( 'type' => 'boolean', 'description' => 'Block VoIP phone numbers from receiving OTPs.' ),
						'gmt_offset'                  => array( 'type' => 'integer', 'description' => 'GMT offset used for transaction log timestamps.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$default_cc = (string) get_mo_option( 'default_country_code' );
					$gmt        = get_mo_option( 'gmt_offset' );
					return array(
						'show_remaining_transactions' => (bool) get_mo_option( 'show_remaining_trans' ),
						'show_dropdown_on_form'       => (bool) get_mo_option( 'show_dropdown_on_form' ),
						'allow_duplicate_phone'       => ! (bool) get_mo_option( 'restrict_duplicates_globally' ),
						'default_country_code'        => $default_cc ? $default_cc : null,
						'remove_plus_sign'            => (bool) get_mo_option( 'remove_plus_sign' ),
						'check_voip'                  => (bool) get_mo_option( 'check_voip' ),
						'gmt_offset'                  => ( false !== $gmt && '' !== $gmt ) ? (int) $gmt : 0,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/update-general-settings ability.
	 *
	 * Saves one or more general plugin settings (e.g. remaining-transaction
	 * display, country dropdown, duplicate phone flag, default country code,
	 * VoIP blocking) and returns a count of how many were updated.
	 *
	 * @return void
	 */
	public static function register_update_general_settings() {
		wp_register_ability(
			'mo-otp/update-general-settings',
			array(
				'label'               => 'Update General Settings',
				'description'         => 'Update general plugin behavior settings. Only fields supplied in the request are changed.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'show_remaining_transactions' => array( 'type' => 'boolean', 'description' => 'Show remaining-transaction counter in admin.' ),
						'show_dropdown_on_form'       => array( 'type' => 'boolean', 'description' => 'Show country-code dropdown on supported forms.' ),
						'allow_duplicate_phone'       => array( 'type' => 'boolean', 'description' => 'Allow multiple accounts to share the same phone number.' ),
						'default_country_code'        => array( 'type' => 'string', 'description' => 'Default country code to prepend (e.g. +1). Leave empty to disable.' ),
						'remove_plus_sign'            => array( 'type' => 'boolean', 'description' => 'Strip the leading + before sending SMS.' ),
						'check_voip'                  => array( 'type' => 'boolean', 'description' => 'Block VoIP phone numbers.' ),
						'gmt_offset'                  => array( 'type' => 'integer', 'minimum' => -12, 'maximum' => 14, 'description' => 'GMT offset for transaction log timestamps (-12 to +14).' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$updated = array();

					$bool_flags = array(
						'show_remaining_transactions' => 'show_remaining_trans',
						'show_dropdown_on_form'       => 'show_dropdown_on_form',
						'remove_plus_sign'            => 'remove_plus_sign',
						'check_voip'                  => 'check_voip',
					);
					foreach ( $bool_flags as $input_key => $option_key ) {
						if ( isset( $input[ $input_key ] ) ) {
							update_mo_option( $option_key, $input[ $input_key ] ? '1' : '' );
							$updated[] = $input_key . ' → ' . ( $input[ $input_key ] ? 'true' : 'false' );
						}
					}

					if ( isset( $input['allow_duplicate_phone'] ) ) {
						update_mo_option( 'restrict_duplicates_globally', $input['allow_duplicate_phone'] ? '' : '1' );
						$updated[] = 'allow_duplicate_phone → ' . ( $input['allow_duplicate_phone'] ? 'true' : 'false' );
					}

					if ( isset( $input['default_country_code'] ) ) {
						update_mo_option( 'default_country_code', sanitize_text_field( $input['default_country_code'] ) );
						$updated[] = 'default_country_code → ' . $input['default_country_code'];
					}

					if ( isset( $input['gmt_offset'] ) ) {
						$offset = max( -12, min( 14, (int) $input['gmt_offset'] ) );
						update_mo_option( 'gmt_offset', $offset );
						$updated[] = 'gmt_offset → ' . $offset;
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid fields provided.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' setting(s) updated.',
						'updated' => $updated,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/get-country-restrictions ability.
	 *
	 * Returns whether country-based blocking is enabled, the block/allow mode
	 * (whitelist or blacklist), and the current list of country codes.
	 *
	 * @return void
	 */
	public static function register_get_country_restrictions() {
		wp_register_ability(
			'mo-otp/get-country-restrictions',
			array(
				'label'               => 'Get Country Restrictions',
				'description'         => 'Get the current country restriction settings from the Country Code addon: mode (allowlist or blocklist) and the country names in each list.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'addon_active'      => array( 'type' => 'boolean', 'description' => 'Whether the Country Code addon is active.' ),
						'mode'              => array( 'type' => array( 'string', 'null' ), 'enum' => array( 'allowlist', 'blocklist', null ), 'description' => 'allowlist = only listed countries can receive OTPs; blocklist = listed countries are blocked.' ),
						'allowed_countries' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Countries in the allowlist.' ),
						'blocked_countries' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Countries in the blocklist.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					if ( ! function_exists( 'mo_get_sc_option' ) ) {
						return array( 'addon_active' => false, 'mode' => null, 'allowed_countries' => array(), 'blocked_countries' => array() );
					}

					$type         = (string) mo_get_sc_option( 'select_country_type' );
					$allowed_raw  = (string) mo_get_sc_option( 'selected_country_list' );
					$blocked_raw  = (string) mo_get_sc_option( 'block_selected_country_list' );
					$split        = function ( $str ) {
						return $str ? array_values( array_filter( array_map( 'trim', preg_split( '/\s*;\s*/', $str ) ) ) ) : array();
					};

					$mode_map = array(
						'select_countries_to_show'  => 'allowlist',
						'select_countries_to_block' => 'blocklist',
					);

					return array(
						'addon_active'      => true,
						'mode'              => $mode_map[ $type ] ?? null,
						'allowed_countries' => $split( $allowed_raw ),
						'blocked_countries' => $split( $blocked_raw ),
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/update-country-restrictions ability.
	 *
	 * Enables or disables country-based blocking, sets the mode (whitelist or
	 * blacklist), and replaces the list of allowed/blocked country codes.
	 *
	 * @return void
	 */
	public static function register_update_country_restrictions() {
		wp_register_ability(
			'mo-otp/update-country-restrictions',
			array(
				'label'               => 'Update Country Restrictions',
				'description'         => 'Update the Country Code addon allowlist or blocklist. Requires the Country Code addon to be installed and active.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'mode'              => array(
							'type'        => 'string',
							'enum'        => array( 'allowlist', 'blocklist', 'disabled' ),
							'description' => 'Set to allowlist, blocklist, or disabled.',
						),
						'allowed_countries' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => 'Country names to allow (used when mode is allowlist).',
						),
						'blocked_countries' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => 'Country names to block (used when mode is blocklist).',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					if ( ! function_exists( 'mo_get_sc_option' ) || ! function_exists( 'mo_update_sc_option' ) ) {
						return array( 'success' => false, 'message' => 'Country Code addon is not active. Please install and activate it first.' );
					}

					$updated  = array();
					$mode_map = array(
						'allowlist' => 'select_countries_to_show',
						'blocklist' => 'select_countries_to_block',
						'disabled'  => '',
					);

					if ( isset( $input['mode'] ) ) {
						mo_update_sc_option( 'select_country_type', $mode_map[ $input['mode'] ] ?? '' );
						$updated[] = 'mode → ' . $input['mode'];
					}

					if ( isset( $input['allowed_countries'] ) && is_array( $input['allowed_countries'] ) ) {
						mo_update_sc_option( 'selected_country_list', implode( '; ', array_map( 'sanitize_text_field', $input['allowed_countries'] ) ) );
						$updated[] = 'allowed_countries → ' . count( $input['allowed_countries'] ) . ' countries';
					}

					if ( isset( $input['blocked_countries'] ) && is_array( $input['blocked_countries'] ) ) {
						mo_update_sc_option( 'block_selected_country_list', implode( '; ', array_map( 'sanitize_text_field', $input['blocked_countries'] ) ) );
						$updated[] = 'blocked_countries → ' . count( $input['blocked_countries'] ) . ' countries';
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid fields provided.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' country restriction setting(s) updated.',
						'updated' => $updated,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/get-rate-limit-settings ability.
	 *
	 * Returns the current rate-limiting options: whether rate limiting is on,
	 * the maximum number of OTP attempts allowed per phone number, and the
	 * block duration in minutes after that limit is exceeded.
	 *
	 * @return void
	 */
	public static function register_get_rate_limit_settings() {
		wp_register_ability(
			'mo-otp/get-rate-limit-settings',
			array(
				'label'               => 'Get Rate Limit Settings',
				'description'         => 'Retrieve OTP spam prevention and rate limiting settings from the OTP Spam Preventer addon.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'addon_active'  => array( 'type' => 'boolean', 'description' => 'Whether the OTP Spam Preventer addon is active.' ),
						'enabled'       => array( 'type' => 'boolean', 'description' => 'Whether spam prevention is currently enabled.' ),
						'max_attempts'  => array( 'type' => 'integer', 'description' => 'Max OTP requests per cooldown window before blocking.' ),
						'cooldown_time' => array( 'type' => 'integer', 'description' => 'Cooldown window in seconds.' ),
						'block_time'    => array( 'type' => 'integer', 'description' => 'Duration in seconds a user is blocked after exceeding max attempts.' ),
						'hourly_limit'  => array( 'type' => 'integer', 'description' => 'Max OTP requests allowed per hour.' ),
						'daily_limit'   => array( 'type' => 'integer', 'description' => 'Max OTP requests allowed per day.' ),
						'whitelist_ips' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'IP addresses exempt from rate limiting.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$raw = get_option( 'mo_osp_settings' );
					if ( false === $raw || ! is_array( $raw ) ) {
						return array(
							'addon_active'  => false,
							'enabled'       => false,
							'max_attempts'  => 3,
							'cooldown_time' => 60,
							'block_time'    => 900,
							'hourly_limit'  => 5,
							'daily_limit'   => 10,
							'whitelist_ips' => array(),
						);
					}

					return array(
						'addon_active'  => true,
						'enabled'       => (bool) ( $raw['enabled'] ?? false ),
						'max_attempts'  => (int) ( $raw['max_attempts'] ?? 3 ),
						'cooldown_time' => (int) ( $raw['cooldown_time'] ?? 60 ),
						'block_time'    => (int) ( $raw['block_time'] ?? 900 ),
						'hourly_limit'  => (int) ( $raw['hourly_limit'] ?? 5 ),
						'daily_limit'   => (int) ( $raw['daily_limit'] ?? 10 ),
						'whitelist_ips' => (array) ( $raw['whitelist_ips'] ?? array() ),
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/update-rate-limit-settings ability.
	 *
	 * Enables or disables rate limiting, and sets the maximum OTP attempts
	 * per phone number and the block duration after that limit is hit.
	 *
	 * @return void
	 */
	public static function register_update_rate_limit_settings() {
		wp_register_ability(
			'mo-otp/update-rate-limit-settings',
			array(
				'label'               => 'Update Rate Limit Settings',
				'description'         => 'Update OTP spam prevention and rate limiting settings. Requires the OTP Spam Preventer addon. hourly_limit must exceed max_attempts; daily_limit must exceed hourly_limit.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'enabled'       => array( 'type' => 'boolean', 'description' => 'Enable or disable spam prevention.' ),
						'max_attempts'  => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 10, 'description' => 'Max OTP requests per cooldown window (1–10).' ),
						'cooldown_time' => array( 'type' => 'integer', 'minimum' => 0, 'description' => 'Cooldown window in seconds.' ),
						'block_time'    => array( 'type' => 'integer', 'minimum' => 60, 'description' => 'Block duration in seconds (min 60).' ),
						'hourly_limit'  => array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Max OTP requests per hour.' ),
						'daily_limit'   => array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Max OTP requests per day.' ),
						'whitelist_ips' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'IP addresses to exempt from rate limiting.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$raw = get_option( 'mo_osp_settings' );
					if ( false === $raw || ! is_array( $raw ) ) {
						$raw = array( 'enabled' => false, 'max_attempts' => 3, 'cooldown_time' => 60, 'block_time' => 900, 'hourly_limit' => 5, 'daily_limit' => 10, 'whitelist_ips' => array() );
					}

					$updated = array();

					foreach ( array( 'max_attempts', 'cooldown_time', 'block_time', 'hourly_limit', 'daily_limit' ) as $key ) {
						if ( isset( $input[ $key ] ) ) {
							$raw[ $key ] = absint( $input[ $key ] );
							$updated[]   = $key . ' → ' . $raw[ $key ];
						}
					}

					if ( isset( $input['enabled'] ) ) {
						$raw['enabled'] = (bool) $input['enabled'];
						$updated[]      = 'enabled → ' . ( $raw['enabled'] ? 'true' : 'false' );
					}

					if ( isset( $input['whitelist_ips'] ) && is_array( $input['whitelist_ips'] ) ) {
						$raw['whitelist_ips'] = array_values( array_filter( array_map( 'sanitize_text_field', $input['whitelist_ips'] ) ) );
						$updated[]            = 'whitelist_ips → ' . count( $raw['whitelist_ips'] ) . ' IPs';
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid fields provided.' );
					}

					if ( $raw['hourly_limit'] <= $raw['max_attempts'] ) {
						return array( 'success' => false, 'message' => 'hourly_limit must be greater than max_attempts.' );
					}
					if ( $raw['daily_limit'] <= $raw['hourly_limit'] ) {
						return array( 'success' => false, 'message' => 'daily_limit must be greater than hourly_limit.' );
					}

					update_option( 'mo_osp_settings', $raw );

					return array(
						'success' => true,
						'message' => count( $updated ) . ' rate limit setting(s) updated.',
						'updated' => $updated,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/get-sms-notifications-settings ability.
	 *
	 * Returns which WooCommerce and Ultimate Member events have SMS
	 * notifications enabled, along with the phone number that admin
	 * alerts are sent to.
	 *
	 * @return void
	 */
	public static function register_get_sms_notifications_settings() {
		wp_register_ability(
			'mo-otp/get-sms-notifications-settings',
			array(
				'label'               => 'Get SMS Notification Settings',
				'description'         => 'Retrieve SMS notification settings for WooCommerce order events and Ultimate Member user events.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'woocommerce'     => array( 'type' => 'object', 'description' => 'WooCommerce SMS notification settings keyed by event.' ),
						'ultimate_member' => array( 'type' => 'object', 'description' => 'Ultimate Member SMS notification settings keyed by event.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$wc_keys = array( 'new_order', 'order_processing', 'order_completed', 'order_cancelled', 'order_refunded', 'order_on_hold', 'order_failed', 'notify_admin' );
					$um_keys = array( 'user_registered', 'user_approved', 'user_rejected' );
					$map     = MoAddonsSettingsAbilities::notification_event_map();

					$build = function ( array $keys ) use ( $map ) {
						$result = array();
						foreach ( $keys as $event ) {
							$prefix            = $map[ $event ];
							$result[ $event ]  = array(
								'enabled'  => (bool) get_option( $prefix . '_enabled' ),
								'template' => (string) get_option( $prefix . '_template', '' ),
							);
						}
						return $result;
					};

					return array(
						'woocommerce'     => $build( $wc_keys ),
						'ultimate_member' => $build( $um_keys ),
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/update-sms-notifications-settings ability.
	 *
	 * Enables or disables SMS notifications for individual WooCommerce and
	 * Ultimate Member events, and sets the admin alert phone number.
	 *
	 * @return void
	 */
	public static function register_update_sms_notifications_settings() {
		wp_register_ability(
			'mo-otp/update-sms-notifications-settings',
			array(
				'label'               => 'Update SMS Notification Settings',
				'description'         => 'Enable or disable an SMS notification event for WooCommerce or Ultimate Member, and update its template.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'event' ),
					'additionalProperties' => false,
					'properties'           => array(
						'event'    => array(
							'type'        => 'string',
							'enum'        => array( 'new_order', 'order_processing', 'order_completed', 'order_cancelled', 'order_refunded', 'order_on_hold', 'order_failed', 'notify_admin', 'user_registered', 'user_approved', 'user_rejected' ),
							'description' => 'The notification event to update.',
						),
						'enabled'  => array( 'type' => 'boolean', 'description' => 'Enable or disable this notification.' ),
						'template' => array( 'type' => 'string', 'description' => 'SMS template for this event. Use {order_id}, {customer_name}, {username} etc. as placeholders.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$event = sanitize_text_field( $input['event'] ?? '' );
					$map   = MoAddonsSettingsAbilities::notification_event_map();

					if ( ! isset( $map[ $event ] ) ) {
						return array( 'success' => false, 'message' => 'Unknown event key.' );
					}

					$prefix  = $map[ $event ];
					$updated = array();

					if ( isset( $input['enabled'] ) ) {
						update_option( $prefix . '_enabled', $input['enabled'] ? '1' : '' );
						$updated[] = $event . '.enabled → ' . ( $input['enabled'] ? 'true' : 'false' );
					}

					if ( isset( $input['template'] ) ) {
						update_option( $prefix . '_template', sanitize_textarea_field( $input['template'] ) );
						$updated[] = $event . '.template → updated';
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'Provide enabled and/or template to update.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' notification setting(s) updated.',
						'updated' => $updated,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/list-available-addons ability.
	 *
	 * Returns a list of all available miniOrange add-ons together with each
	 * add-on's name, description, whether it is currently installed and
	 * active, and the plan required to use it.
	 *
	 * @return void
	 */
	public static function register_list_available_addons() {
		wp_register_ability(
			'mo-otp/list-available-addons',
			array(
				'label'               => 'List Available Addons',
				'description'         => 'List all registered miniOrange OTP plugin addons and their active status.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'total'  => array( 'type' => 'integer' ),
						'addons' => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$addons = array();

					foreach ( AddOnList::instance()->get_list() as $key => $addon ) {
						$name = method_exists( $addon, 'get_addon_name' ) ? $addon->get_addon_name()
							  : ( method_exists( $addon, 'get_name' ) ? $addon->get_name() : $key );

						$entry = array( 'key' => $key, 'name' => $name, 'active' => true );

						if ( method_exists( $addon, 'is_enabled' ) ) {
							$entry['enabled'] = (bool) $addon->is_enabled();
						} elseif ( method_exists( $addon, 'is_addon_enabled' ) ) {
							$entry['enabled'] = (bool) $addon->is_addon_enabled();
						}

						$addons[] = $entry;
					}

					$registered_keys = array_column( $addons, 'key' );
					$known = array(
						array( 'key' => 'countrycode',      'name' => 'Country Code Restriction', 'active' => function_exists( 'mo_get_sc_option' ) ),
						array( 'key' => 'otpspampreventer', 'name' => 'OTP Spam Preventer',        'active' => false !== get_option( 'mo_osp_settings' ) ),
					);
					foreach ( $known as $item ) {
						if ( ! in_array( $item['key'], $registered_keys, true ) ) {
							$addons[] = $item;
						}
					}

					return array( 'total' => count( $addons ), 'addons' => $addons );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/get-transaction-cost-estimate ability.
	 *
	 * Given a destination country code and a message length, returns the
	 * estimated SMS transaction cost and the number of SMS segments that
	 * will be consumed for that message.
	 *
	 * @return void
	 */
	public static function register_get_transaction_cost_estimate() {
		wp_register_ability(
			'mo-otp/get-transaction-cost-estimate',
			array(
				'label'               => 'Get Transaction Cost Estimate',
				'description'         => 'Get remaining transaction quotas and whether they are being consumed (miniOrange gateway) or bypassed (custom gateway).',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'sms_remaining'      => array( 'type' => 'integer', 'description' => 'Remaining SMS OTP transactions.' ),
						'email_remaining'    => array( 'type' => 'integer', 'description' => 'Remaining Email OTP transactions.' ),
						'whatsapp_remaining' => array( 'type' => 'integer', 'description' => 'Remaining WhatsApp OTP transactions.' ),
						'gateway_type'       => array( 'type' => array( 'string', 'null' ), 'description' => 'Active gateway type.' ),
						'using_mo_gateway'   => array( 'type' => 'boolean', 'description' => 'True if using the miniOrange shared gateway (consumes transactions).' ),
						'cost_note'          => array( 'type' => 'string', 'description' => 'Human-readable note about transaction consumption.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$gateway_type    = (string) get_mo_option( 'custome_gateway_type' );
					$using_mo        = empty( $gateway_type ) || 'MoGateway' === $gateway_type;

					return array(
						'sms_remaining'      => absint( get_mo_option( 'phone_transactions_remaining' ) ),
						'email_remaining'    => absint( get_mo_option( 'email_transactions_remaining' ) ),
						'whatsapp_remaining' => absint( get_mo_option( 'whatsapp_transactions_remaining' ) ),
						'gateway_type'       => $gateway_type ? $gateway_type : 'MoGateway',
						'using_mo_gateway'   => $using_mo,
						'cost_note'          => $using_mo
							? 'Each OTP consumes 1 transaction from your miniOrange quota.'
							: 'Using a custom gateway. Transactions are billed by your gateway provider.',
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the mo-otp/get-form-field-mappings ability.
	 *
	 * Returns the phone and email field mappings configured for every active
	 * form integration. Each entry shows which form field the plugin reads
	 * the phone number and email address from during OTP verification.
	 *
	 * @return void
	 */
	public static function register_get_form_field_mappings() {
		wp_register_ability(
			'mo-otp/get-form-field-mappings',
			array(
				'label'               => 'Get Form Field Mappings',
				'description'         => 'Get the phone and email field key mappings for all form integrations that require them. Highlights any forms where a required key is missing.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'total'    => array( 'type' => 'integer' ),
						'mappings' => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$registry = MoFormManagementAbilities::get_form_registry();
					$mappings = array();

					foreach ( $registry as $form_key => $config ) {
						if ( empty( $config['phone_key_option'] ) && empty( $config['email_key_option'] ) ) {
							continue;
						}

						$phone_key = ! empty( $config['phone_key_option'] )
							? (string) MoFormManagementAbilities::get_form_option_value( $config, 'phone_key_option' )
							: null;
						$email_key = ! empty( $config['email_key_option'] )
							? (string) MoFormManagementAbilities::get_form_option_value( $config, 'email_key_option' )
							: null;

						$mappings[] = array(
							'form_key'          => $form_key,
							'name'              => $config['name'],
							'enabled'           => (bool) MoFormManagementAbilities::get_form_option_value( $config, 'enable_option' ),
							'phone_key'         => $phone_key ?: null,
							'phone_key_missing' => ! empty( $config['phone_key_option'] ) && empty( $phone_key ),
							'email_key'         => $email_key ?: null,
							'email_key_missing' => ! empty( $config['email_key_option'] ) && empty( $email_key ),
						);
					}

					return array( 'total' => count( $mappings ), 'mappings' => $mappings );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => true,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}
}
