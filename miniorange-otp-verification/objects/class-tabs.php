<?php
/**Load Tabs
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Tabs' ) ) {
	/**
	 * This class is used to define the base url of tabs of plugin
	 */
	final class Tabs {

		const FORMS            = 'forms';
		const NOTIFICATIONS    = 'notifications';
		const ACCOUNT          = 'account';
		const OTP_SETTINGS     = 'otp_settings';
		const SMS_EMAIL_CONFIG = 'sms_email_config';
		const MESSAGES         = 'messages';
		const GATEWAY          = 'gateway';
		const CONTACT_US       = 'contact_us';
		const PRICING          = 'pricing';
		const ADD_ONS          = 'addons';
		const CUSTOM_FORM      = 'customization';
		const WHATSAPP         = 'whatsapp';
		const CUSTOMIZATION    = 'custom_work';
		const REPORTING        = 'transaction_report';
		const CUSTOM_MSG       = 'custom_message';

	}
}
