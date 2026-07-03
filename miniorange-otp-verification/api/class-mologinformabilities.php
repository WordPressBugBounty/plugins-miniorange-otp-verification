<?php
/**
 * Login form abilities.
 *
 * Registers:
 *   mo-otp/get-login-form-settings
 *   mo-otp/update-login-form-settings
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers login form OTP abilities.
 */
class MoLoginFormAbilities {

	/**
	 * Registers all login form abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_login_form_settings();
		static::register_update_login_form_settings();
	}

	/**
	 * Registers the 'mo-otp/get-login-form-settings' ability.
	 *
	 * This ability reads all current OTP settings for the WordPress,
	 * WooCommerce, and Ultimate Member login forms — including whether
	 * OTP is enabled, the verification channel (phone or email), login
	 * mode, redirection settings, and button labels.
	 *
	 * @return void
	 */
	public static function register_get_login_form_settings() {
		wp_register_ability(
			'mo-otp/get-login-form-settings',
			array(
				'label'               => 'Get Login Form Settings',
				'description'         => 'Read all OTP settings for the WordPress / WooCommerce / Ultimate Member Login Form.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'enabled'                   => array( 'type' => 'boolean', 'description' => 'Whether OTP verification is active on the login form.' ),
						'otp_type'                  => array( 'type' => array( 'string', 'null' ), 'enum' => array( 'PHONE', 'EMAIL', null ), 'description' => 'Active verification channel.' ),
						'whatsapp_enabled'          => array( 'type' => 'boolean', 'description' => 'Whether OTP over WhatsApp is enabled.' ),
						'phone_meta_key'            => array( 'type' => 'string', 'description' => 'User meta key where the phone number is stored.' ),
						'allow_phone_registration'  => array( 'type' => 'boolean', 'description' => 'Allow user to add a phone number if one does not exist.' ),
						'allow_phone_login'         => array( 'type' => 'boolean', 'description' => 'Allow users to log in using their phone number.' ),
						'restrict_duplicate_phones' => array( 'type' => 'boolean', 'description' => 'Prevent multiple accounts from sharing the same phone number.' ),
						'login_mode'                => array( 'type' => 'string', 'enum' => array( '2fa', 'otp_only' ), 'description' => '2fa = Password + OTP; otp_only = OTP login only.' ),
						'bypass_admin'              => array( 'type' => 'boolean', 'description' => 'Allow the administrator to bypass OTP verification.' ),
						'delay_otp'                 => array( 'type' => 'boolean', 'description' => 'Delay OTP verification.' ),
						'delay_otp_interval'        => array( 'type' => array( 'integer', 'null' ), 'description' => 'Delay interval in minutes (used when delay_otp is true).' ),
						'redirection_type'          => array( 'type' => 'string', 'enum' => array( 'default', 'custom' ), 'description' => 'default = current page; custom = specific page.' ),
						'redirection_page_id'       => array( 'type' => array( 'integer', 'null' ), 'description' => 'Page ID to redirect to (used when redirection_type is custom).' ),
						'otp_button_text'           => array( 'type' => 'string', 'description' => 'Label on the Login With OTP button.' ),
						'password_button_text'      => array( 'type' => 'string', 'description' => 'Label on the Login With Password button.' ),
						'password_button_css'       => array( 'type' => 'string', 'description' => 'Inline CSS for the Login With Password button.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$raw_type    = (string) get_mo_option( 'wp_login_enable_type' );
					$otp_type    = null;
					if ( 'mo_wp_login_phone_enable' === $raw_type ) {
						$otp_type = 'PHONE';
					} elseif ( 'mo_wp_login_email_enable' === $raw_type ) {
						$otp_type = 'EMAIL';
					}

					$redirect_raw  = (string) get_mo_option( 'wp_login_redirection_enable' );
					$redirect_type = ( 'redirect_to_the_page' === $redirect_raw ) ? 'custom' : 'default';

					$delay_interval_raw = get_mo_option( 'wp_login_delay_otp_interval' );

					return array(
						'enabled'                   => (bool) get_mo_option( 'wp_login_enable' ),
						'otp_type'                  => $otp_type,
						'whatsapp_enabled'          => (bool) get_mo_option( 'mo_whatsapp_enable' ),
						'phone_meta_key'            => (string) get_mo_option( 'wp_login_key' ),
						'allow_phone_registration'  => (bool) get_mo_option( 'wp_login_register_phone' ),
						'allow_phone_login'         => (bool) get_mo_option( 'wp_login_allow_phone_login' ),
						'restrict_duplicate_phones' => (bool) get_mo_option( 'wp_login_restrict_duplicates' ),
						'login_mode'                => get_mo_option( 'wp_login_skip_password' ) ? 'otp_only' : '2fa',
						'bypass_admin'              => (bool) get_mo_option( 'wp_login_bypass_admin' ),
						'delay_otp'                 => (bool) get_mo_option( 'wp_login_delay_otp' ),
						'delay_otp_interval'        => $delay_interval_raw ? absint( $delay_interval_raw ) : null,
						'redirection_type'          => $redirect_type,
						'redirection_page_id'       => get_mo_option( 'login_custom_redirect' ) ? absint( get_mo_option( 'login_custom_redirect' ) ) : null,
						'otp_button_text'           => (string) get_mo_option( 'wp_login_with_otp_button_text' ),
						'password_button_text'      => (string) get_mo_option( 'wp_login_with_pass_button_text' ),
						'password_button_css'       => (string) get_mo_option( 'wp_login_with_pass_button_css' ),
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/update-login-form-settings' ability.
	 *
	 * This ability updates one or more settings for the login form OTP
	 * flow. You can change the verification channel, login mode (2FA or
	 * OTP-only), redirection after login, button labels, and more.
	 * Only the fields you provide are updated; others stay unchanged.
	 *
	 * @return void
	 */
	public static function register_update_login_form_settings() {
		wp_register_ability(
			'mo-otp/update-login-form-settings',
			array(
				'label'               => 'Update Login Form Settings',
				'description'         => 'Update any setting for the WordPress / WooCommerce / Ultimate Member Login Form.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'enabled'                   => array( 'type' => 'boolean', 'description' => 'Enable or disable OTP on the login form.' ),
						'otp_type'                  => array( 'type' => 'string', 'enum' => array( 'PHONE', 'EMAIL' ), 'description' => 'Verification channel — PHONE or EMAIL.' ),
						'whatsapp_enabled'          => array( 'type' => 'boolean', 'description' => 'Enable OTP over WhatsApp.' ),
						'phone_meta_key'            => array( 'type' => 'string', 'description' => 'User meta key where the phone number is stored (default: phone).' ),
						'allow_phone_registration'  => array( 'type' => 'boolean', 'description' => 'Allow user to add a phone if one does not exist.' ),
						'allow_phone_login'         => array( 'type' => 'boolean', 'description' => 'Allow users to log in using their phone number.' ),
						'restrict_duplicate_phones' => array( 'type' => 'boolean', 'description' => 'Prevent multiple accounts sharing the same phone.' ),
						'login_mode'                => array( 'type' => 'string', 'enum' => array( '2fa', 'otp_only' ), 'description' => '2fa = Password + OTP; otp_only = OTP login only.' ),
						'bypass_admin'              => array( 'type' => 'boolean', 'description' => 'Let the administrator bypass OTP.' ),
						'delay_otp'                 => array( 'type' => 'boolean', 'description' => 'Delay OTP verification.' ),
						'delay_otp_interval'        => array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Delay interval in minutes.' ),
						'redirection_type'          => array( 'type' => 'string', 'enum' => array( 'default', 'custom' ), 'description' => 'default = current page; custom = specific page.' ),
						'redirection_page_id'       => array( 'type' => 'integer', 'description' => 'Page ID to redirect to after login (used when redirection_type is custom).' ),
						'otp_button_text'           => array( 'type' => 'string', 'description' => 'Label on the Login With OTP button.' ),
						'password_button_text'      => array( 'type' => 'string', 'description' => 'Label on the Login With Password button.' ),
						'password_button_css'       => array( 'type' => 'string', 'description' => 'Inline CSS for the Login With Password button.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'updated' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					),
					'required'   => array( 'success', 'message' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$updated = array();

					if ( isset( $input['enabled'] ) ) {
						update_mo_option( 'wp_login_enable', $input['enabled'] ? '1' : '' );
						$updated[] = 'enabled → ' . ( $input['enabled'] ? 'true' : 'false' );
					}

					if ( isset( $input['otp_type'] ) ) {
						$tag = 'PHONE' === strtoupper( $input['otp_type'] ) ? 'mo_wp_login_phone_enable' : 'mo_wp_login_email_enable';
						update_mo_option( 'wp_login_enable_type', $tag );
						$updated[] = 'otp_type → ' . strtoupper( $input['otp_type'] );
					}

					if ( isset( $input['whatsapp_enabled'] ) ) {
						update_mo_option( 'mo_whatsapp_enable', $input['whatsapp_enabled'] ? '1' : '' );
						$updated[] = 'whatsapp_enabled → ' . ( $input['whatsapp_enabled'] ? 'true' : 'false' );
					}

					if ( isset( $input['phone_meta_key'] ) ) {
						update_mo_option( 'wp_login_key', sanitize_text_field( $input['phone_meta_key'] ) );
						$updated[] = 'phone_meta_key → ' . $input['phone_meta_key'];
					}

					if ( isset( $input['allow_phone_registration'] ) ) {
						update_mo_option( 'wp_login_register_phone', $input['allow_phone_registration'] ? '1' : '' );
						$updated[] = 'allow_phone_registration → ' . ( $input['allow_phone_registration'] ? 'true' : 'false' );
					}

					if ( isset( $input['allow_phone_login'] ) ) {
						update_mo_option( 'wp_login_allow_phone_login', $input['allow_phone_login'] ? '1' : '' );
						$updated[] = 'allow_phone_login → ' . ( $input['allow_phone_login'] ? 'true' : 'false' );
					}

					if ( isset( $input['restrict_duplicate_phones'] ) ) {
						update_mo_option( 'wp_login_restrict_duplicates', $input['restrict_duplicate_phones'] ? '1' : '' );
						$updated[] = 'restrict_duplicate_phones → ' . ( $input['restrict_duplicate_phones'] ? 'true' : 'false' );
					}

					if ( isset( $input['login_mode'] ) ) {
						update_mo_option( 'wp_login_skip_password', 'otp_only' === $input['login_mode'] ? '1' : '' );
						$updated[] = 'login_mode → ' . $input['login_mode'];
					}

					if ( isset( $input['bypass_admin'] ) ) {
						update_mo_option( 'wp_login_bypass_admin', $input['bypass_admin'] ? '1' : '' );
						$updated[] = 'bypass_admin → ' . ( $input['bypass_admin'] ? 'true' : 'false' );
					}

					if ( isset( $input['delay_otp'] ) ) {
						update_mo_option( 'wp_login_delay_otp', $input['delay_otp'] ? '1' : '' );
						$updated[] = 'delay_otp → ' . ( $input['delay_otp'] ? 'true' : 'false' );
					}

					if ( isset( $input['delay_otp_interval'] ) ) {
						update_mo_option( 'wp_login_delay_otp_interval', absint( $input['delay_otp_interval'] ) );
						$updated[] = 'delay_otp_interval → ' . $input['delay_otp_interval'];
					}

					if ( isset( $input['redirection_type'] ) ) {
						$val = 'custom' === $input['redirection_type'] ? 'redirect_to_the_page' : 'redirect_to_default';
						update_mo_option( 'wp_login_redirection_enable', $val );
						$updated[] = 'redirection_type → ' . $input['redirection_type'];
					}

					if ( isset( $input['redirection_page_id'] ) ) {
						update_mo_option( 'login_custom_redirect', absint( $input['redirection_page_id'] ) );
						$updated[] = 'redirection_page_id → ' . $input['redirection_page_id'];
					}

					if ( isset( $input['otp_button_text'] ) ) {
						update_mo_option( 'wp_login_with_otp_button_text', sanitize_text_field( $input['otp_button_text'] ) );
						$updated[] = 'otp_button_text → ' . $input['otp_button_text'];
					}

					if ( isset( $input['password_button_text'] ) ) {
						update_mo_option( 'wp_login_with_pass_button_text', sanitize_text_field( $input['password_button_text'] ) );
						$updated[] = 'password_button_text → ' . $input['password_button_text'];
					}

					if ( isset( $input['password_button_css'] ) ) {
						update_mo_option( 'wp_login_with_pass_button_css', sanitize_textarea_field( $input['password_button_css'] ) );
						$updated[] = 'password_button_css → ' . $input['password_button_css'];
					}

					if ( empty( $updated ) ) {
						return array( 'success' => false, 'message' => 'No valid fields provided.' );
					}

					return array(
						'success' => true,
						'message' => count( $updated ) . ' setting(s) updated successfully.',
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
}
