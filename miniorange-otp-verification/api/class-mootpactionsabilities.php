<?php
/**
 * OTP send/verify abilities.
 *
 * Registers:
 *   mo-otp/send-otp
 *   mo-otp/verify-otp
 *   mo-otp/send-notification-sms
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoUtility;
use OTP\Objects\NotificationSettings;

/**
 * Registers OTP send and verify abilities.
 */
class MoOtpActionsAbilities {

	/**
	 * Registers all OTP action abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_send_otp();
		static::register_verify_otp();
		static::register_send_notification_sms();
	}

	/**
	 * Registers the 'mo-otp/send-otp' ability.
	 *
	 * This ability sends an OTP to a user over the chosen channel — SMS,
	 * Email, or WhatsApp — using the miniOrange gateway. Provide `phone`
	 * for the sms and whatsapp channels, or `email` for the email channel.
	 * It returns a transaction ID that must be passed to verify-otp to
	 * confirm the code entered by the user. The WhatsApp channel requires
	 * WhatsApp to be enabled and configured in the plugin settings first.
	 *
	 * @return void
	 */
	public static function register_send_otp() {
		wp_register_ability(
			'mo-otp/send-otp',
			array(
				'label'               => 'Send OTP',
				'description'         => 'Send a One-Time Password (OTP) over the chosen channel (sms, email, or whatsapp) using the configured MiniOrange gateway. Provide phone for sms/whatsapp or email for email. Returns a transaction ID to pass to verify-otp.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'channel' => array(
							'type'        => 'string',
							'enum'        => array( 'sms', 'email', 'whatsapp' ),
							'description' => 'Delivery channel for the OTP: sms, email, or whatsapp.',
						),
						'phone'   => array(
							'type'        => 'string',
							'description' => 'Phone number including country code, starting with + (e.g. +919766755338). Required for the sms and whatsapp channels.',
						),
						'email'   => array(
							'type'        => 'string',
							'description' => 'Valid email address to deliver the OTP to. Required for the email channel.',
						),
					),
					'required'             => array( 'channel' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'tx_id'   => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message', 'tx_id' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$channel = strtolower( sanitize_text_field( $input['channel'] ?? '' ) );

					switch ( $channel ) {
						case 'sms':
							$phone = MoUtility::process_phone_number( sanitize_text_field( $input['phone'] ?? '' ) );
							if ( empty( $phone ) ) {
								return array( 'success' => false, 'message' => 'A valid phone number is required for the sms channel.', 'tx_id' => '' );
							}
							$result        = GatewayFunctions::instance()->mo_send_otp_token( 'SMS', '', $phone );
							$channel_label = 'SMS';
							break;

						case 'whatsapp':
							if ( ! get_mo_option( 'mo_whatsapp_enable' ) ) {
								return array( 'success' => false, 'message' => 'WhatsApp is not enabled. Please configure WhatsApp in the plugin settings.', 'tx_id' => '' );
							}
							$phone = MoUtility::process_phone_number( sanitize_text_field( $input['phone'] ?? '' ) );
							if ( empty( $phone ) ) {
								return array( 'success' => false, 'message' => 'A valid phone number is required for the whatsapp channel.', 'tx_id' => '' );
							}
							$result        = GatewayFunctions::instance()->mo_send_otp_token( 'WHATSAPP', '', $phone );
							$channel_label = 'WhatsApp';
							break;

						case 'email':
							$email = sanitize_email( $input['email'] ?? '' );
							if ( empty( $email ) ) {
								return array( 'success' => false, 'message' => 'A valid email address is required for the email channel.', 'tx_id' => '' );
							}
							$result        = GatewayFunctions::instance()->mo_send_otp_token( 'EMAIL', $email, '' );
							$channel_label = 'Email';
							break;

						default:
							return array( 'success' => false, 'message' => 'Invalid channel. Must be one of: sms, email, whatsapp.', 'tx_id' => '' );
					}

					if ( 'SUCCESS' === ( $result['status'] ?? '' ) ) {
						return array(
							'success' => true,
							'message' => 'OTP sent successfully via ' . $channel_label . '.',
							'tx_id'   => $result['txId'] ?? '',
						);
					}

					return array(
						'success' => false,
						'message' => $result['message'] ?? 'Failed to send OTP. Check MiniOrange gateway configuration.',
						'tx_id'   => '',
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
	 * Registers the 'mo-otp/verify-otp' ability.
	 *
	 * This ability checks whether the OTP code entered by a user is correct.
	 * It takes the transaction ID returned by send-otp, the OTP the user
	 * typed, and the channel type (SMS or EMAIL). Returns success true if
	 * the code matches, false otherwise.
	 *
	 * @return void
	 */
	public static function register_verify_otp() {
		wp_register_ability(
			'mo-otp/verify-otp',
			array(
				'label'               => 'Verify OTP',
				'description'         => 'Verify a One-Time Password against a transaction ID returned by send-otp.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'tx_id' => array(
							'type'        => 'string',
							'description' => 'Transaction ID returned by send-otp.',
						),
						'otp'   => array(
							'type'        => 'string',
							'description' => 'The OTP code entered by the user.',
						),
						'type'  => array(
							'type'        => 'string',
							'enum'        => array( 'SMS', 'EMAIL' ),
							'description' => 'The OTP type — SMS or EMAIL.',
						),
					),
					'required'             => array( 'tx_id', 'otp', 'type' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$tx_id  = sanitize_text_field( $input['tx_id'] ?? '' );
					$otp    = sanitize_text_field( $input['otp'] ?? '' );
					$type   = sanitize_text_field( $input['type'] ?? 'SMS' );
					$result = GatewayFunctions::instance()->mo_validate_otp_token( $tx_id, $otp, $type );

					if ( 'SUCCESS' === ( $result['status'] ?? '' ) ) {
						return array(
							'success' => true,
							'message' => 'OTP verified successfully.',
						);
					}

					return array(
						'success' => false,
						'message' => $result['message'] ?? 'OTP verification failed. Please check the code and try again.',
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
	 * Registers the 'mo-otp/send-notification-sms' ability.
	 *
	 * This ability sends a plain custom SMS message to a phone number —
	 * not an OTP, but any text you provide (for example, a welcome message
	 * or a reminder). It uses the same miniOrange gateway as the OTP flow.
	 *
	 * @return void
	 */
	public static function register_send_notification_sms() {
		wp_register_ability(
			'mo-otp/send-notification-sms',
			array(
				'label'               => 'Send Custom SMS Notification',
				'description'         => 'Send a custom (non-OTP) SMS notification to a phone number using the configured MiniOrange gateway.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'phone'   => array(
							'type'        => 'string',
							'description' => 'Phone number including country code, starting with + (e.g. +919766755338).',
						),
						'message' => array(
							'type'        => 'string',
							'description' => 'The SMS message text to send.',
						),
					),
					'required'             => array( 'phone', 'message' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$phone   = MoUtility::process_phone_number( sanitize_text_field( $input['phone'] ?? '' ) );
					$message = sanitize_textarea_field( $input['message'] ?? '' );

					if ( empty( $phone ) || empty( $message ) ) {
						return array(
							'success' => false,
							'message' => 'Phone number and message are required.',
						);
					}

					$settings = new NotificationSettings( $phone, $message );
					$result   = GatewayFunctions::instance()->mo_send_notif( $settings );

					$success = ! empty( $result ) && 'SUCCESS' === strtoupper( (string) $result );
					return array(
						'success' => $success,
						'message' => $success ? 'SMS notification sent successfully.' : 'Failed to send SMS notification. Check gateway configuration.',
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
