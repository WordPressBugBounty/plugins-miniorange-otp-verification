<?php
/**
 * Helper functions for Woocommerce New Customer Notifications
 *
 * @package miniorange-otp-verification/Notifications
 */

namespace OTP\Notifications\WcSMSNotification\Helper\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\WcSMSNotification\Helper\MoWcAddOnMessages;
use OTP\Helper\MoUtility;
use OTP\Objects\SMSNotification;

/**
 * This class is used to handle all the settings and function related
 * to the WooCommerce New Customer SMS Notification. It initializes the
 * notification related settings and implements the functionality for
 * sending the SMS to the user.
 */
if ( ! class_exists( 'WooCommerceNewCustomerNotification' ) ) {
	/**
	 * WooCommerceNewCustomerNotification class
	 */
	class WooCommerceNewCustomerNotification extends SMSNotification {

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
			$this->title             = 'New Account';
			$this->page              = 'wc_new_customer_notif';
			$this->is_enabled        = false;
			$this->tool_tip_header   = 'NEW_CUSTOMER_NOTIF_HEADER';
			$this->tool_tip_body     = 'NEW_CUSTOMER_NOTIF_BODY';
			$this->recipient         = 'customer';
			$this->sms_body          = get_wc_option( 'woocommerce_registration_generate_password', '' ) === 'yes'
									? MoWcAddOnMessages::showMessage( MoWcAddOnMessages::NEW_CUSTOMER_SMS_WITH_PASS )
									: MoWcAddOnMessages::showMessage( MoWcAddOnMessages::NEW_CUSTOMER_SMS );
			$this->default_sms_body  = get_wc_option( 'woocommerce_registration_generate_password', '' ) === 'yes'
									? MoWcAddOnMessages::showMessage( MoWcAddOnMessages::NEW_CUSTOMER_SMS_WITH_PASS )
									: MoWcAddOnMessages::showMessage( MoWcAddOnMessages::NEW_CUSTOMER_SMS );
			$this->premium_tags      = '{user-email},{registration-date},{accountpage-url}';
			$this->available_tags    = '{site-name},{username}';
			$this->page_header       = mo_( 'NEW ACCOUNT NOTIFICATION SETTINGS' );
			$this->page_description  = mo_( 'SMS notifications settings for New Account creation SMS sent to the users' );
			$this->notification_type = mo_( 'Customer' );
			$this->sms_tags          = '{username};{site-name};{accountpage-url}';
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
			$this->set_notif_in_session( $this->page );
			$customer_id         = $args['customer_id'];
			$customer_data       = $args['new_customer_data'];
			$site_name           = get_bloginfo();
			$username            = get_userdata( $customer_id )->user_login;
			$phone_number        = get_user_meta( $customer_id, 'billing_phone', true );
			$posted_phone_number = MoUtility::sanitize_check( 'billing_phone', $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No need for nonce verification as the function is called on third party plugin hook.
			$phone_number        = MoUtility::is_blank( $phone_number ) && $posted_phone_number ? $posted_phone_number : $phone_number;
			$accountpage         = wc_get_page_permalink( 'myaccount' );// phpcs:ignore -- Default function of Woocommerce.

			$replaced_string = array(
				'site-name'       => get_bloginfo(),
				'username'        => $username,
				'accountpage-url' => $accountpage,
			);

			/* WooCommerce Premium Tags */
			$replaced_string = apply_filters( 'new_customer', $replaced_string, $args ); // hook call which will store the all the premium Tags.

			$replaced_string = apply_filters( 'mo_wc_new_customer_notif_string_replace', $replaced_string );
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
