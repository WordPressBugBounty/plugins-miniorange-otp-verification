<?php
/**
 * Diagnostics and premium forms abilities.
 *
 * Registers:
 *   mo-otp/list-premium-forms
 *   mo-otp/run-diagnostics
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\GatewayFunctions;
use OTP\Helper\PremiumFeatureList;

/**
 * Registers diagnostics and premium forms abilities.
 */
class MoDiagnosticsAbilities {

	/**
	 * Registers all diagnostics abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_list_premium_forms();
		static::register_run_diagnostics();
	}

	/**
	 * Registers the 'mo-otp/list-premium-forms' ability.
	 *
	 * This ability returns a list of all form integrations that are locked
	 * behind a paid plan, along with the plan name needed to unlock each
	 * one. Useful for AI assistants to inform users why a form key is
	 * unavailable before they try to enable it.
	 *
	 * @return void
	 */
	public static function register_list_premium_forms() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/list-premium-forms',
			array(
				'label'               => 'List Premium Forms',
				'description'         => 'List all form integrations that require a paid plan, including the plan name required to unlock each one. These forms show a crown icon in the plugin UI.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'total' => array( 'type' => 'integer' ),
						'forms' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'form_key'  => array( 'type' => 'string' ),
									'name'      => array( 'type' => 'string' ),
									'plan_name' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$premium_forms = PremiumFeatureList::instance()->get_premium_forms();
					$forms         = array();
					foreach ( $premium_forms as $form_key => $details ) {
						$forms[] = array(
							'form_key'  => $form_key,
							'name'      => $details['name'],
							'plan_name' => $details['plan_name'],
						);
					}
					return array(
						'total' => count( $forms ),
						'forms' => $forms,
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
	 * Registers the 'mo-otp/run-diagnostics' ability.
	 *
	 * This ability scans the plugin configuration and reports any problems
	 * it finds — such as a missing gateway, empty SMS quota, an out-of-range
	 * OTP length, or a WhatsApp token that is not set. Each issue includes
	 * a severity level (error, warning, info) and a plain-English fix hint.
	 *
	 * @return void
	 */
	public static function register_run_diagnostics() {
		MoAbilitiesConstants::register_ability(
			'mo-otp/run-diagnostics',
			array(
				'label'               => 'Run Diagnostics',
				'description'         => 'Check the plugin configuration for common errors and return a list of issues with suggested fixes.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'healthy' => array(
							'type'        => 'boolean',
							'description' => 'True if no issues were found.',
						),
						'issues'  => array(
							'type'        => 'array',
							'description' => 'List of detected configuration issues.',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'code'     => array( 'type' => 'string' ),
									'severity' => array(
										'type' => 'string',
										'enum' => array( 'error', 'warning', 'info' ),
									),
									'message'  => array( 'type' => 'string' ),
									'fix'      => array( 'type' => 'string' ),
								),
							),
						),
						'summary' => array(
							'type'        => 'string',
							'description' => 'Human-readable summary of the diagnostic result.',
						),
					),
					'required'   => array( 'healthy', 'issues', 'summary' ),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $_input ) {
					$issues = array();

					if ( ! GatewayFunctions::instance()->is_gateway_config() ) {
						$issues[] = array(
							'code'     => 'gateway_not_configured',
							'severity' => 'error',
							'message'  => 'The SMS/Email gateway is not configured.',
							'fix'      => 'Go to the plugin settings and configure your miniOrange gateway credentials.',
						);
					}

					if ( ! get_mo_option( 'admin_customer_key' ) ) {
						$issues[] = array(
							'code'     => 'account_not_registered',
							'severity' => 'error',
							'message'  => 'No miniOrange account is linked to this plugin.',
							'fix'      => 'Register or log in to your miniOrange account inside the plugin settings.',
						);
					}

					$sms_remaining = absint( get_mo_option( 'phone_transactions_remaining' ) );
					if ( 0 === $sms_remaining ) {
						$issues[] = array(
							'code'     => 'sms_quota_exhausted',
							'severity' => 'error',
							'message'  => 'SMS transaction quota is 0. No OTPs can be sent via SMS.',
							'fix'      => 'Purchase additional SMS transactions from your miniOrange account.',
						);
					} elseif ( $sms_remaining < 10 ) {
						$issues[] = array(
							'code'     => 'sms_quota_low',
							'severity' => 'warning',
							'message'  => 'SMS transaction quota is critically low (' . $sms_remaining . ' remaining).',
							'fix'      => 'Top up your SMS transaction balance soon.',
						);
					}

					$email_remaining = absint( get_mo_option( 'email_transactions_remaining' ) );
					if ( 0 === $email_remaining ) {
						$issues[] = array(
							'code'     => 'email_quota_exhausted',
							'severity' => 'error',
							'message'  => 'Email transaction quota is 0. No OTPs can be sent via Email.',
							'fix'      => 'Purchase additional email transactions from your miniOrange account.',
						);
					} elseif ( $email_remaining < 10 ) {
						$issues[] = array(
							'code'     => 'email_quota_low',
							'severity' => 'warning',
							'message'  => 'Email transaction quota is critically low (' . $email_remaining . ' remaining).',
							'fix'      => 'Top up your email transaction balance soon.',
						);
					}

					$otp_length = absint( get_mo_option( 'otp_length' ) );
					if ( $otp_length < 4 || $otp_length > 10 ) {
						$issues[] = array(
							'code'     => 'invalid_otp_length',
							'severity' => 'error',
							'message'  => 'OTP length is ' . $otp_length . ', which is outside the valid range (4–10).',
							'fix'      => 'Call update-otp-settings with otp_length between 4 and 10.',
						);
					}

					$otp_validity = absint( get_mo_option( 'otp_validity' ) );
					if ( $otp_validity < 1 || $otp_validity > 60 ) {
						$issues[] = array(
							'code'     => 'invalid_otp_validity',
							'severity' => 'error',
							'message'  => 'OTP validity is ' . $otp_validity . ' minutes, which is outside the valid range (1–60).',
							'fix'      => 'Call update-otp-settings with otp_validity_minutes between 1 and 60.',
						);
					}

					if ( get_mo_option( 'mo_whatsapp_enable' ) ) {
						$wa_type  = get_mo_option( 'mo_whatsapp_type' );
						$wa_token = get_mo_option( 'mo_whatsapp_access_token' );
						if ( 'bussiness_whatsapp' === $wa_type && empty( $wa_token ) ) {
							$issues[] = array(
								'code'     => 'whatsapp_missing_token',
								'severity' => 'error',
								'message'  => 'WhatsApp is enabled but the access token is missing.',
								'fix'      => 'Enter your WhatsApp Business access token in the plugin settings.',
							);
						}
					}

					$blocked_phones  = array_filter( explode( ';', (string) get_mo_option( 'blocked_phone_numbers' ) ) );
					$blocked_domains = array_filter( explode( ';', (string) get_mo_option( 'blocked_domains' ) ) );

					if ( ! empty( $blocked_phones ) ) {
						$issues[] = array(
							'code'     => 'blocked_phones_present',
							'severity' => 'info',
							'message'  => count( $blocked_phones ) . ' phone number(s) are blocked: ' . implode( ', ', $blocked_phones ) . '.',
							'fix'      => 'Call unblock-phone if any of these should be allowed.',
						);
					}

					if ( ! empty( $blocked_domains ) ) {
						$issues[] = array(
							'code'     => 'blocked_domains_present',
							'severity' => 'info',
							'message'  => count( $blocked_domains ) . ' email domain(s) are blocked: ' . implode( ', ', $blocked_domains ) . '.',
							'fix'      => 'Call unblock-email-domain if any of these should be allowed.',
						);
					}

					$error_count   = count( array_filter( $issues, function ( $i ) { return 'error' === $i['severity']; } ) );
					$warning_count = count( array_filter( $issues, function ( $i ) { return 'warning' === $i['severity']; } ) );
					$healthy       = 0 === $error_count;

					if ( $healthy && 0 === $warning_count ) {
						$summary = 'All systems healthy. No configuration issues found.';
					} elseif ( $healthy ) {
						$summary = $warning_count . ' warning(s) found. Plugin is functional but attention is recommended.';
					} else {
						$summary = $error_count . ' error(s) and ' . $warning_count . ' warning(s) found. Plugin may not work correctly.';
					}

					return array(
						'healthy' => $healthy,
						'issues'  => array_values( $issues ),
						'summary' => $summary,
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
}
