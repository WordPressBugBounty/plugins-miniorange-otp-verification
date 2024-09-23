<?php
/**
 * Helper functions for Woocommerce Order Cancelled Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Helper\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnMessages;
use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnUtility;
use OTP\Helper\MoUtility;
use OTP\Objects\SMSNotification;

/**
 * This class is used to handle all the settings and function related
 * to the WooCommerce Order Cancelled SMS Notification. It initializes the
 * notification related settings and implements the functionality for
 * sending the SMS to the user.
 */
if ( ! class_exists( 'WooCommerceOrderCancelledNotification' ) ) {
	/**
	 * WooCommerceOrderCancelledNotification class
	 */
	class WooCommerceOrderCancelledNotification extends SMSNotification {

		/** Global Variable
		 *
		 * @var instance - initiates the instance of the file.
		 */
		public static $instance;

		/**
		 * Woocommerce premium tags.
		 *
		 * @var array
		 */
		public $premium_tags;

		/** Declare Default variables */
		protected function __construct() {
			parent::__construct();
			$this->title             = 'Order Cancelled';
			$this->page              = 'wc_order_cancelled_notif';
			$this->is_enabled        = false;
			$this->tool_tip_header   = 'ORDER_CANCELLED_NOTIF_HEADER';
			$this->tool_tip_body     = 'ORDER_CANCELLED_NOTIF_BODY';
			$this->recipient         = 'customer';
			$this->sms_body          = MoWcAddOnMessages::showMessage( MoWcAddOnMessages::ORDER_CANCELLED_SMS );
			$this->default_sms_body  = MoWcAddOnMessages::showMessage( MoWcAddOnMessages::ORDER_CANCELLED_SMS );
			$this->available_tags    = '{site-name},{order-number},{username},{order-date}';
			$this->premium_tags      = '{payment-method},{total-Amount},{transaction-ID},{order-key},{billing-firstName},{billing-phone},{billing-email},{billing-address},{billing-city},{billing-state},{billing-postcode},{billing-country},{shipping-firstName},{shipping-phone},{shipping-address},{shipping-city},{shipping-state},{shipping-postcode},{shipping-country}';
			$this->page_header       = mo_( 'ORDER CANCELLED NOTIFICATION SETTINGS' );
			$this->page_description  = mo_( 'SMS notifications settings for Order Cancellation SMS sent to the users' );
			$this->notification_type = mo_( 'Customer' );
			$this->sms_tags          = '{username};{order-number};{site-name};{order-date}';
			$this->template_name     = null;
			self::$instance          = $this;
		}


		/**
		 * Checks if there exists an existing instance of the class.
		 * If not then creates an instance and returns it.
		 */
		public static function getInstance() {
			return null === self::$instance ? new self() : self::$instance;
		}


		/**
		 * Initialize all the variables required to modify the sms template
		 * and send the SMS to the user. Checks if the SMS notification
		 * has been enabled and send SMS to the user. Do not send SMS
		 * if phone number of the customer doesn't exist.
		 *
		 * @param  array $args all the arguments required to send SMS.
		 */
		public function send_sms( array $args ) {
			if ( ! $this->is_enabled ) {
				return;
			}
			$order_details = $args['orderDetails'];
			if ( MoUtility::is_blank( $order_details ) ) {
				return;
			}
			$this->set_notif_in_session( $this->page );
			$userdetails  = get_userdata( $order_details->get_customer_id() );
			$site_name    = get_bloginfo();
			$username     = MoUtility::is_blank( $userdetails ) ? '' : $userdetails->user_login;
			$phone_number = MoWcAddOnUtility::get_customer_number_from_order( $order_details );
			$date_created = $order_details->get_date_created()->date_i18n();
			$order_no     = $order_details->get_order_number();

			$replaced_string = array(
				'site-name'    => $site_name,
				'username'     => $username,
				'order-date'   => $date_created,
				'order-number' => $order_no,
			);

			/* WooCommerce Premium Tags */
			$replaced_string = apply_filters( 'mo_premium_tags', $replaced_string, $args ); // hook call which will store the all the premium Tags.

			$replaced_string = apply_filters( 'mo_wc_customer_order_cancelled_notif_string_replace', $replaced_string );
			$sms_body        = MoUtility::replace_string( $replaced_string, $this->sms_body );
			$sms_tags        = MoUtility::replace_string( $replaced_string, $this->sms_tags );

			if ( MoUtility::is_blank( $phone_number ) ) {
				return;
			}
			if ( get_wc_option( 'mo_whatsapp_notification_enable', 'mo_wp_sms_' ) ) {
				MoUtility::send_whatsapp_notif( $phone_number, $this->template_name, $sms_tags );
			} else {
				MoUtility::send_phone_notif( $phone_number, $sms_body );
			}
		}
	}
}
