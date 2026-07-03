<?php
/**
 * Account, support, and OTP resend abilities.
 *
 * Registers:
 *   mo-otp/get-account-status
 *   mo-otp/register-account
 *   mo-otp/submit-support-query
 *   mo-otp/submit-feedback
 *   mo-otp/resend-otp
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\GatewayFunctions;
use OTP\Helper\MoUtility;
use OTP\Helper\MocURLCall;

/**
 * Registers account, support, and resend-OTP abilities.
 */
class MoAccountAbilities {

	/**
	 * Registers all account management abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_get_account_status();
		static::register_register_account();
		static::register_submit_support_query();
		static::register_submit_feedback();
		static::register_resend_otp();
	}

	/**
	 * Registers the 'mo-otp/get-account-status' ability.
	 *
	 * This ability returns whether a miniOrange account is linked to this
	 * plugin, along with the account email, customer key, remaining SMS
	 * and email transaction counts, and whether a premium license is active.
	 *
	 * @return void
	 */
	public static function register_get_account_status() {
		wp_register_ability(
			'mo-otp/get-account-status',
			array(
				'label'               => 'Get Account Status',
				'description'         => 'Retrieve the current miniOrange account status: whether an account is linked, the registered email, license type, and transaction quotas.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'registered'          => array( 'type' => 'boolean', 'description' => 'Whether a miniOrange account is linked.' ),
						'email'               => array( 'type' => array( 'string', 'null' ), 'description' => 'Registered account email.' ),
						'customer_id'         => array( 'type' => array( 'string', 'null' ), 'description' => 'Customer ID (partial, for display).' ),
						'registration_status' => array( 'type' => array( 'string', 'null' ), 'description' => 'Internal registration status flag.' ),
						'sms_transactions'    => array( 'type' => 'integer', 'description' => 'Remaining SMS transactions.' ),
						'email_transactions'  => array( 'type' => 'integer', 'description' => 'Remaining Email transactions.' ),
						'gateway_configured'  => array( 'type' => 'boolean', 'description' => 'Whether the SMS/Email gateway is configured.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$customer_id     = (string) get_mo_option( 'admin_customer_key' );
					$email           = (string) get_mo_option( 'admin_email' );
					$reg_status      = (string) get_mo_option( 'registration_status' );
					$sms_remaining   = absint( get_mo_option( 'phone_transactions_remaining' ) );
					$email_remaining = absint( get_mo_option( 'email_transactions_remaining' ) );

					return array(
						'registered'          => ! empty( $customer_id ),
						'email'               => $email ? $email : null,
						'customer_id'         => $customer_id ? substr( $customer_id, 0, 4 ) . '****' : null,
						'registration_status' => $reg_status ? $reg_status : null,
						'sms_transactions'    => $sms_remaining,
						'email_transactions'  => $email_remaining,
						'gateway_configured'  => (bool) GatewayFunctions::instance()->is_gateway_config(),
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
	 * Registers the 'mo-otp/register-account' ability.
	 *
	 * This ability creates a new miniOrange account and links it to the
	 * plugin, or links an existing account by logging in with an email
	 * and password. A linked account is required before the plugin can
	 * send OTPs or verify a license key.
	 *
	 * @return void
	 */
	public static function register_register_account() {
		wp_register_ability(
			'mo-otp/register-account',
			array(
				'label'               => 'Register Account',
				'description'         => 'Register a new miniOrange account or log in to an existing one. On success the customer ID and token are stored locally and the gateway becomes available.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'email', 'password' ),
					'additionalProperties' => false,
					'properties'           => array(
						'email'      => array( 'type' => 'string', 'description' => 'Email address for the miniOrange account.' ),
						'password'   => array( 'type' => 'string', 'description' => 'Password (minimum 6 characters).' ),
						'first_name' => array( 'type' => 'string', 'description' => 'First name (used when creating a new account).' ),
						'last_name'  => array( 'type' => 'string', 'description' => 'Last name (used when creating a new account).' ),
						'company'    => array( 'type' => 'string', 'description' => 'Company name (used when creating a new account).' ),
						'phone'      => array( 'type' => 'string', 'description' => 'Phone number (used when creating a new account).' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'status'  => array( 'type' => 'string', 'description' => 'API status code from miniOrange.' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$email    = sanitize_email( $input['email'] ?? '' );
					$password = sanitize_text_field( $input['password'] ?? '' );

					if ( empty( $email ) || empty( $password ) ) {
						return array( 'success' => false, 'message' => 'email and password are required.', 'status' => 'MISSING_FIELDS' );
					}

					if ( strlen( $password ) < 6 ) {
						return array( 'success' => false, 'message' => 'Password must be at least 6 characters.', 'status' => 'INVALID_PASSWORD' );
					}

					$check      = json_decode( MocURLCall::check_customer( $email ), true );
					$api_status = strtoupper( $check['status'] ?? '' );

					if ( 'CUSTOMER_NOT_FOUND' === $api_status ) {
						$result     = json_decode(
							MocURLCall::create_customer(
								$email,
								sanitize_text_field( $input['company'] ?? '' ),
								$password,
								sanitize_text_field( $input['phone'] ?? '' ),
								sanitize_text_field( $input['first_name'] ?? '' ),
								sanitize_text_field( $input['last_name'] ?? '' )
							),
							true
						);
						$new_status = strtoupper( $result['status'] ?? '' );

						if ( 'SUCCESS' === $new_status ) {
							static::store_account_credentials( $email, $result );
							return array( 'success' => true, 'message' => 'Account created and linked successfully.', 'status' => $new_status );
						}

						return array( 'success' => false, 'message' => 'Account creation failed: ' . ( $result['message'] ?? $new_status ), 'status' => $new_status );
					}

					$result     = json_decode( MocURLCall::get_customer_key( $email, $password ), true );
					$new_status = strtoupper( $result['status'] ?? '' );

					if ( 'SUCCESS' === $new_status ) {
						static::store_account_credentials( $email, $result );
						return array( 'success' => true, 'message' => 'Account linked successfully.', 'status' => $new_status );
					}

					return array( 'success' => false, 'message' => 'Login failed: ' . ( $result['message'] ?? $new_status ), 'status' => $new_status );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => true,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/submit-support-query' ability.
	 *
	 * This ability sends a support request to the miniOrange team. Provide
	 * the registered email and a description of the issue. The support
	 * team will reply to that email address.
	 *
	 * @return void
	 */
	public static function register_submit_support_query() {
		wp_register_ability(
			'mo-otp/submit-support-query',
			array(
				'label'               => 'Submit Support Query',
				'description'         => 'Submit a support query to the miniOrange support team.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'email', 'query' ),
					'additionalProperties' => false,
					'properties'           => array(
						'email' => array( 'type' => 'string', 'description' => 'Email address to reply to.' ),
						'query' => array( 'type' => 'string', 'description' => 'Description of the issue or question.' ),
						'phone' => array( 'type' => 'string', 'description' => 'Optional phone number for contact.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$email = sanitize_email( $input['email'] ?? '' );
					$query = sanitize_textarea_field( $input['query'] ?? '' );
					$phone = sanitize_text_field( $input['phone'] ?? '' );

					if ( empty( $email ) || empty( $query ) ) {
						return array( 'success' => false, 'message' => 'email and query are required.' );
					}

					$sent = MocURLCall::submit_contact_us( $email, $phone, $query, home_url(), 'OTP Plugin Support Query' );

					if ( $sent ) {
						return array( 'success' => true, 'message' => 'Support query submitted successfully. The team will respond to ' . $email . '.' );
					}

					return array( 'success' => false, 'message' => 'Failed to submit support query. Please try again or email otpsupport@xecurify.com directly.' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => true,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/submit-feedback' ability.
	 *
	 * This ability sends a feedback message to the miniOrange team.
	 * Use it to share suggestions, report a bug, or rate your experience
	 * with the plugin. No account is required to submit feedback.
	 *
	 * @return void
	 */
	public static function register_submit_feedback() {
		wp_register_ability(
			'mo-otp/submit-feedback',
			array(
				'label'               => 'Submit Feedback',
				'description'         => 'Submit plugin feedback or a rating to miniOrange.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'feedback' ),
					'additionalProperties' => false,
					'properties'           => array(
						'feedback' => array( 'type' => 'string', 'description' => 'Feedback text to submit.' ),
						'rating'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 5,
							'description' => 'Optional star rating (1–5).',
						),
						'email'    => array( 'type' => 'string', 'description' => 'Optional email address for the feedback.' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$feedback = sanitize_textarea_field( $input['feedback'] ?? '' );
					$rating   = isset( $input['rating'] ) ? absint( $input['rating'] ) : 0;
					$email    = sanitize_email( $input['email'] ?? ( (string) get_mo_option( 'admin_email' ) ) );

					if ( empty( $feedback ) ) {
						return array( 'success' => false, 'message' => 'feedback text is required.' );
					}

					$query = 'Plugin Feedback' . ( $rating ? ' (Rating: ' . $rating . '/5)' : '' ) . ': ' . $feedback;
					$sent  = MocURLCall::submit_contact_us( $email, '', $query, home_url(), 'OTP Plugin Feedback' );

					if ( $sent ) {
						return array( 'success' => true, 'message' => 'Feedback submitted successfully. Thank you!' );
					}

					return array( 'success' => false, 'message' => 'Failed to submit feedback. Please try again.' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => true,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/resend-otp' ability.
	 *
	 * This ability resends a fresh OTP to a user via their chosen channel
	 * (SMS, Email, or WhatsApp). It generates a new OTP and a new
	 * transaction ID, replacing any previous pending code.
	 *
	 * @return void
	 */
	public static function register_resend_otp() {
		wp_register_ability(
			'mo-otp/resend-otp',
			array(
				'label'               => 'Resend OTP',
				'description'         => 'Resend an OTP to a phone number or email address via SMS, Email, or WhatsApp.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'type' ),
					'additionalProperties' => false,
					'properties'           => array(
						'type'  => array(
							'type'        => 'string',
							'enum'        => array( 'SMS', 'EMAIL', 'WHATSAPP' ),
							'description' => 'Channel to resend the OTP via.',
						),
						'phone' => array(
							'type'        => 'string',
							'description' => 'Phone number including country code (required for SMS and WHATSAPP).',
						),
						'email' => array(
							'type'        => 'string',
							'description' => 'Email address (required for EMAIL).',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'required'   => array( 'success', 'message', 'tx_id' ),
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'tx_id'   => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$type  = strtoupper( sanitize_text_field( $input['type'] ?? '' ) );
					$phone = MoUtility::process_phone_number( sanitize_text_field( $input['phone'] ?? '' ) );
					$email = sanitize_email( $input['email'] ?? '' );

					if ( 'EMAIL' === $type ) {
						if ( empty( $email ) ) {
							return array( 'success' => false, 'message' => 'email is required for EMAIL type.', 'tx_id' => '' );
						}
						$result = GatewayFunctions::instance()->mo_send_otp_token( 'EMAIL', $email, '' );
					} elseif ( 'WHATSAPP' === $type ) {
						return array_merge( MoAbilitiesConstants::premium_required_response(), array( 'tx_id' => '' ) );
					} else {
						if ( empty( $phone ) ) {
							return array( 'success' => false, 'message' => 'phone is required for SMS type.', 'tx_id' => '' );
						}
						$result = GatewayFunctions::instance()->mo_send_otp_token( 'SMS', '', $phone );
					}

					if ( 'SUCCESS' === ( $result['status'] ?? '' ) ) {
						return array( 'success' => true, 'message' => 'OTP resent successfully via ' . $type . '.', 'tx_id' => $result['txId'] ?? '' );
					}

					return array( 'success' => false, 'message' => $result['message'] ?? 'Failed to resend OTP. Check gateway configuration.', 'tx_id' => '' );
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
	 * Persists customer credentials returned by the miniOrange API after a successful login or registration.
	 */
	private static function store_account_credentials( $email, array $result ) {
		update_mo_option( 'admin_customer_key', $result['id'] );
		update_mo_option( 'customer_api_key', $result['apiKey'] );
		update_mo_option( 'customer_token', $result['token'] );
		update_mo_option( 'admin_email', $email );
		update_mo_option( 'registration_status', 'MO_CUSTOMER_VALIDATION_REGISTRATION_COMPLETE' );
	}
}
