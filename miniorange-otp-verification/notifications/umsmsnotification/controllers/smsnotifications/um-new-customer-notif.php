<?php
/**
 * Load admin view for Ultimate member new customer notifications.
 *
 * @package miniorange-otp-verification/addons/umsmsnotification/controllers/smsnotifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\MoUtility;
use OTP\Helper\MoMessages;
use OTP\Notifications\UmSMSNotification\Helper\UltimateMemberNotificationsList;

$notification_settings = maybe_unserialize( get_umsn_option( 'notification_settings_option' ) );
$notification_settings = $notification_settings ? $notification_settings : UltimateMemberNotificationsList::instance();
$sms                   = '';

$sms_settings       = $notification_settings->get_um_new_customer_notif();
$enable_disable_tag = $sms_settings->page;
$textarea_tag       = $sms_settings->page . '_smsbody';
$recipient_tag      = $sms_settings->page . '_recipient';
$recipient_value    = maybe_unserialize( $sms_settings->recipient );
$recipient_value    = MoUtility::is_blank( $recipient_value ) ? 'mobile_number' : $recipient_value;
$enable_disable     = $sms_settings->is_enabled ? 'checked' : '';

require UMSN_DIR . '/views/smsnotifications/um-customer-sms-template.php';
