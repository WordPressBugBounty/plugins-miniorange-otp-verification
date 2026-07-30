<?php
/**
 * OTP settings read/mutate abilities.
 *
 * Registers:
 *   mo-otp/get-settings
 *   mo-otp/get-transactions-remaining
 *   mo-otp/update-otp-settings
 *   mo-otp/manage-blocklist
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoUtility;

/**
 * Registers OTP settings read and mutate abilities.
 */
class MoOtpSettingsAbilities {

	/**
	 * Registers all OTP settings abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_settings();
		static::register_get_transactions_remaining();
		static::register_update_otp_settings();
		static::register_manage_blocklist();
	}

	/**
	 * Registers the 'mo-otp/get-settings' ability.
	 *
	 * This ability returns a snapshot of the current OTP configuration:
	 * OTP length, validity period, gateway status, WhatsApp toggle,
	 * remaining SMS and email transactions, and the lists of blocked
	 * phone numbers and email domains.
	 *
	 * @return void
	 */
	public static function register_get_settings() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-settings',
			array(
				'label'               => 'Get OTP Settings',
				'description'         => 'Read the current OTP plugin configuration: OTP length, validity period, gateway status, and remaining transaction quota.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'otp_length'                   => array(
							'type'        => 'integer',
							'description' => 'Number of digits in the OTP (4–10).',
						),
						'otp_validity_minutes'         => array(
							'type'        => 'integer',
							'description' => 'How long (in minutes) the OTP remains valid (1–60).',
						),
						'gateway_configured'           => array(
							'type'        => 'boolean',
							'description' => 'Whether the SMS/Email gateway is configured.',
						),
						'whatsapp_enabled'             => array(
							'type'        => 'boolean',
							'description' => 'Whether WhatsApp OTP is enabled.',
						),
						'sms_transactions_remaining'   => array(
							'type'        => 'integer',
							'description' => 'Remaining SMS OTP transactions.',
						),
						'email_transactions_remaining' => array(
							'type'        => 'integer',
							'description' => 'Remaining Email OTP transactions.',
						),
						'blocked_phone_numbers'        => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => 'List of blocked phone numbers.',
						),
						'blocked_email_domains'        => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => 'List of blocked email domains.',
						),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$otp_length   = absint( get_mo_option( 'otp_length' ) );
					$otp_validity = absint( get_mo_option( 'otp_validity' ) );

					$blocked_phones_raw  = (string) get_mo_option( 'blocked_phone_numbers' );
					$blocked_domains_raw = (string) get_mo_option( 'blocked_domains' );

					$blocked_phones  = array_values( array_filter( explode( ';', $blocked_phones_raw ) ) );
					$blocked_domains = array_values( array_filter( explode( ';', $blocked_domains_raw ) ) );

					return array(
						'otp_length'                   => $otp_length > 0 ? $otp_length : 5,
						'otp_validity_minutes'         => $otp_validity > 0 ? $otp_validity : 5,
						'gateway_configured'           => (bool) GatewayFunctions::instance()->is_gateway_config(),
						'whatsapp_enabled'             => (bool) get_mo_option( 'mo_whatsapp_enable' ),
						'sms_transactions_remaining'   => absint( get_mo_option( 'phone_transactions_remaining' ) ),
						'email_transactions_remaining' => absint( get_mo_option( 'email_transactions_remaining' ) ),
						'blocked_phone_numbers'        => $blocked_phones,
						'blocked_email_domains'        => $blocked_domains,
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
	 * Registers the 'mo-otp/get-transactions-remaining' ability.
	 *
	 * This ability returns the current remaining transaction counts for
	 * SMS, Email, and WhatsApp OTP channels. Use this to quickly check
	 * whether you need to top up your miniOrange balance before sending
	 * more OTPs.
	 *
	 * @return void
	 */
	public static function register_get_transactions_remaining() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/get-transactions-remaining',
			array(
				'label'               => 'Get Remaining Transactions',
				'description'         => 'Read the remaining SMS, Email, and WhatsApp OTP transaction quota from the MiniOrange account.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'sms'      => array(
							'type'        => 'integer',
							'description' => 'Remaining SMS OTP transactions.',
						),
						'email'    => array(
							'type'        => 'integer',
							'description' => 'Remaining Email OTP transactions.',
						),
						'whatsapp' => array(
							'type'        => 'integer',
							'description' => 'Remaining WhatsApp OTP transactions.',
						),
					),
					'required'   => array( 'sms', 'email', 'whatsapp' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					return array(
						'sms'      => absint( get_mo_option( 'phone_transactions_remaining' ) ),
						'email'    => absint( get_mo_option( 'email_transactions_remaining' ) ),
						'whatsapp' => absint( get_mo_option( 'whatsapp_transactions_remaining', 'mowp_customer_validation_' ) ),
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
	 * Registers the 'mo-otp/update-otp-settings' ability.
	 *
	 * This ability updates the OTP length (how many digits the OTP has,
	 * between 4 and 10) and the OTP validity period (how many minutes
	 * the OTP stays valid before it expires, between 1 and 60).
	 *
	 * @return void
	 */
	public static function register_update_otp_settings() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/update-otp-settings',
			array(
				'label'               => 'Update OTP Settings',
				'description'         => 'Update the OTP length (4–10 digits) and OTP validity period (1–60 minutes).',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'otp_length'           => array(
							'type'        => 'integer',
							'minimum'     => 4,
							'maximum'     => 10,
							'description' => 'Number of digits in the OTP (4–10).',
						),
						'otp_validity_minutes' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 60,
							'description' => 'How long (in minutes) the OTP remains valid (1–60).',
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'              => array( 'type' => 'boolean' ),
						'message'              => array( 'type' => 'string' ),
						'otp_length'           => array( 'type' => 'integer' ),
						'otp_validity_minutes' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'success', 'message' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$updated = array();

					if ( isset( $input['otp_length'] ) ) {
						return MoAbilitiesConstants::premium_required_response();
					}

					if ( isset( $input['otp_validity_minutes'] ) ) {
						$validity = absint( $input['otp_validity_minutes'] );
						if ( $validity >= 1 && $validity <= 60 ) {
							update_mo_option( 'otp_validity', $validity );
							$updated['otp_validity_minutes'] = $validity;
						} else {
							return array(
								'success' => false,
								'message' => 'otp_validity_minutes must be between 1 and 60.',
							);
						}
					}

					if ( empty( $updated ) ) {
						return array(
							'success' => false,
							'message' => 'No valid settings provided. Supply otp_length and/or otp_validity_minutes.',
						);
					}

					return array_merge(
						array(
							'success' => true,
							'message' => 'OTP settings updated successfully.',
						),
						$updated
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
	 * Registers the 'mo-otp/manage-blocklist' ability.
	 *
	 * This ability blocks or unblocks a phone number or an email domain
	 * from receiving OTPs. Set `target` to phone or email_domain, `action`
	 * to block or unblock, and `value` to the number/domain to act on.
	 * Blocked phone numbers and blocked email domains from disposable or
	 * known-bad domains will not receive any OTPs from this plugin. The
	 * call returns the updated block list for the chosen target.
	 *
	 * @return void
	 */
	public static function register_manage_blocklist() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/manage-blocklist',
			array(
				'label'               => 'Manage OTP Block List',
				'description'         => 'Block or unblock a phone number or an email domain from receiving OTPs. Set target to phone or email_domain, action to block or unblock, and value to the number/domain.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'target' => array(
							'type'        => 'string',
							'enum'        => array( 'phone', 'email_domain' ),
							'description' => 'What to act on: a phone number (phone) or an email domain (email_domain).',
						),
						'action' => array(
							'type'        => 'string',
							'enum'        => array( 'block', 'unblock' ),
							'description' => 'Whether to block or unblock the value.',
						),
						'value'  => array(
							'type'        => 'string',
							'description' => 'The phone number (e.g. +919766755338) or email domain (e.g. spam.com) to act on.',
						),
					),
					'required'             => array( 'target', 'action', 'value' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'               => array( 'type' => 'boolean' ),
						'message'               => array( 'type' => 'string' ),
						'blocked_phone_numbers' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'blocked_email_domains' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'success', 'message' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$target = sanitize_text_field( $input['target'] ?? '' );
					$action = sanitize_text_field( $input['action'] ?? '' );

					if ( ! in_array( $target, array( 'phone', 'email_domain' ), true ) ) {
						return array( 'success' => false, 'message' => 'Invalid target. Must be phone or email_domain.' );
					}
					if ( ! in_array( $action, array( 'block', 'unblock' ), true ) ) {
						return array( 'success' => false, 'message' => 'Invalid action. Must be block or unblock.' );
					}

					if ( 'phone' === $target ) {
						$value      = MoUtility::process_phone_number( sanitize_text_field( $input['value'] ?? '' ) );
						$option_key = 'blocked_phone_numbers';
						$list_key   = 'blocked_phone_numbers';
						$noun       = 'Phone number';
						if ( empty( $value ) ) {
							return array( 'success' => false, 'message' => 'A valid phone number is required.' );
						}
					} else {
						$value      = strtolower( trim( sanitize_text_field( $input['value'] ?? '' ), ". \t\n\r\0\x0B" ) );
						$option_key = 'blocked_domains';
						$list_key   = 'blocked_email_domains';
						$noun       = 'Email domain';
						if ( empty( $value ) ) {
							return array( 'success' => false, 'message' => 'A valid domain is required (e.g. spam.com).' );
						}
					}

					$existing = array_filter( explode( ';', (string) get_mo_option( $option_key ) ) );

					if ( 'block' === $action ) {
						if ( in_array( $value, $existing, true ) ) {
							return array( 'success' => true, 'message' => $noun . ' is already blocked.', $list_key => array_values( $existing ) );
						}
						$existing[] = $value;
						update_mo_option( $option_key, implode( ';', $existing ) );
						return array( 'success' => true, 'message' => $noun . ' blocked successfully.', $list_key => array_values( $existing ) );
					}

					$updated = array_values( array_filter( $existing, function ( $item ) use ( $value ) { return $item !== $value; } ) );

					if ( count( $existing ) === count( $updated ) ) {
						return array( 'success' => true, 'message' => $noun . ' was not in the block list.', $list_key => $updated );
					}

					update_mo_option( $option_key, implode( ';', $updated ) );
					return array( 'success' => true, 'message' => $noun . ' unblocked successfully.', $list_key => $updated );
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
}
