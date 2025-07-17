<?php
/**Load Abstract Class SubTabs
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Subtabs class.
 */
if ( ! class_exists( 'SubTabs' ) ) {
	/**
	 * SubTabs class
	 */
	final class SubTabs {
		const MO_FREE_FORMS       = 'free_forms';
		const MO_PREMIUM_FORMS    = 'premium_forms';
		const MO_OTP_SETTINGS     = 'otp_settings';
		const MO_GENERAL_SETTINGS = 'general_settings';
		const MO_GATEWAY_CONFIG   = 'gateway_config';
		const MO_MESSAGE_BOX      = 'message_box';
		const MO_POPUP_DESIGN     = 'popup_design';
		const MO_UM_NOTIF         = 'um_notification';
		const MO_WC_NOTIF         = 'wc_notification';
		const MO_FREE_ADDONS      = 'free_addons';
		const MO_PREMIUM_ADDONS   = 'premium_addons';
		const MO_REPORTING        = 'reporting';
		const MO_TEMPLATE_CONFIG  = 'template_configurations';
		const MO_DOKAN_NOTIF      = 'dokan_vendor_notifications';
		const MO_WCFM_NOTIF       = 'wcfm_vendor_notifications';
		const MO_FORM_NOTIF       = 'form_notification';

	}
}
