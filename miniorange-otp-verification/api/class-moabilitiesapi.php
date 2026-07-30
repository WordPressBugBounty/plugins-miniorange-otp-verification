<?php
/**
 * WordPress Abilities API integration for MiniOrange OTP Verification.
 *
 * Orchestrates all mo-otp ability registrations by delegating to focused
 * classes grouped by responsibility:
 *
 *   MoOtpActionsAbilities        — send-otp, verify-otp, send-notification-sms
 *   MoOtpSettingsAbilities       — get-settings, get-transactions-remaining, update-otp-settings,
 *                                  manage-blocklist
 *   MoFormManagementAbilities    — list-forms, get-form-details, update-form-settings, enable-form, disable-form
 *   MoLoginFormAbilities         — get-login-form-settings, update-login-form-settings
 *   MoDiagnosticsAbilities       — list-premium-forms, run-diagnostics
 *   MoCustomizationAbilities     — get-popup-templates, update-popup-template,
 *                                  get-custom-messages, update-custom-messages
 *   MoGatewayConfigAbilities     — get-gateway-config, configure-gateway,
 *                                  get-whatsapp-settings, update-whatsapp-settings,
 *                                  get-email-templates, update-email-template
 *   MoReportingAbilities         — enable-transaction-logging, get-transaction-logs,
 *                                  export-transaction-logs, clear-transaction-logs
 *   MoAccountAbilities           — get-account-status, register-account,
 *                                  submit-support-query, submit-feedback, resend-otp
 *   MoAddonsSettingsAbilities    — get-general-settings, update-general-settings,
 *                                  get-country-restrictions, update-country-restrictions,
 *                                  get-rate-limit-settings, update-rate-limit-settings,
 *                                  get-sms-notifications-settings, update-sms-notifications-settings,
 *                                  list-available-addons, get-transaction-cost-estimate,
 *                                  get-form-field-mappings
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Traits\Instance;

require_once __DIR__ . '/class-moabilitiesconstants.php';

require_once __DIR__ . '/' . MoAbilitiesConstants::OTP_ACTIONS_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::OTP_SETTINGS_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::FORM_MANAGEMENT_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::LOGIN_FORM_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::DIAGNOSTICS_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::CUSTOMIZATION_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::GATEWAY_CONFIG_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::REPORTING_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::ACCOUNT_FILE;
require_once __DIR__ . '/' . MoAbilitiesConstants::ADDONS_SETTINGS_FILE;

if ( ! class_exists( 'OTP\API\MoAbilitiesApi' ) ) {

	/**
	 * Registers MiniOrange OTP abilities with the WordPress Abilities API.
	 */
	class MoAbilitiesApi {

		use Instance;

		/**
		 * Hooks the category and ability registration into the WordPress Abilities API init actions.
		 */
		public function __construct() {
			add_action( 'wp_abilities_api_categories_init', array( static::class, 'register_category' ) );
			add_action( 'wp_abilities_api_init', array( static::class, 'register_abilities' ) );
		}

		/**
		 * Registers the mo-otp ability category with the WordPress Abilities API.
		 *
		 * @return void
		 */
		public static function register_category() {
			MoAbilitiesConstants::register_ability_category(
				'mo-otp',
				array(
					'label'       => 'OTP Verification',
					'description' => 'miniOrange OTP Verification — send, verify, and configure One-Time Passwords via SMS, Email, and WhatsApp.',
				)
			);
		}

		/**
		 * Calls register_all() on every ability class, loading all mo-otp abilities.
		 *
		 * @return void
		 */
		public static function register_abilities() {
			MoOtpActionsAbilities::register_all();
			MoOtpSettingsAbilities::register_all();
			MoFormManagementAbilities::register_all();
			MoLoginFormAbilities::register_all();
			MoDiagnosticsAbilities::register_all();
			MoCustomizationAbilities::register_all();
			MoGatewayConfigAbilities::register_all();
			MoReportingAbilities::register_all();
			MoAccountAbilities::register_all();
			MoAddonsSettingsAbilities::register_all();
		}
	}
}
