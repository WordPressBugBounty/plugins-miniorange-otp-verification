<?php
/**
 * Utility functions for Woocommerce Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\MoUtility;
use WC_Order;

/**
 * This class is used to define some plugin wide utility
 * functions. The functions described here are all static
 * in nature so that it's accessible without an instance.
 */
if ( ! class_exists( 'MoWcAddOnUtility' ) ) {
	/**
	 * MoWcAddOnUtility class
	 */
	class MoWcAddOnUtility {


		/**
		 * Get the Phone of the first Admin user. This is used as
		 * the recipient of the admin SMS notifications if no
		 * phone number is saved for the notification.
		 *
		 * @return string
		 */
		public static function get_admin_phone_number() {
			$notification_settings = get_wc_option( 'notification_settings_option' );
			if ( $notification_settings ) {
				$sms_settings    = $notification_settings->get_wc_admin_order_status_notif(); // phpcs::ignore -- $notification_settings is an object.
				$recipient_value = maybe_unserialize( $sms_settings->recipient );
			}
			return ! empty( $recipient_value ) ? $recipient_value : '';
		}

		/**
		 * Get the billing phone number of Customer. If the billing number is not set
		 * with the order, pick the number from registered phone number.
		 *
		 * @param WC_Order $order   - Order details, WooCommerce Order Object.
		 * @return string
		 */
		public static function get_customer_number_from_order( $order ) {
			$user_id = $order->get_user_id();
			$phone   = $order->get_billing_phone();
			return ! empty( $phone ) ? $phone : get_user_meta( $user_id, 'billing_phone', true );
		}


		/**
		 * Checks if the customer is registered or not and shows a message on the page
		 * to the user so that they can register or login themselves to use the plugin.
		 */
		public static function is_addon_activated() {
			MoUtility::is_addon_activated();
		}
	}
}
