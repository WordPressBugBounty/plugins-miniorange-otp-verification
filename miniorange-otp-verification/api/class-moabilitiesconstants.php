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
}
