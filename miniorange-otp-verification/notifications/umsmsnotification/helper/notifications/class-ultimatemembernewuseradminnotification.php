<?php
/**
 * Ultimate Member New Customer Notifications helper
 *
 * @package miniorange-otp-verification/Notifications/umsmsnotification/helper/notifications
 */

namespace OTP\Notifications\UmSMSNotification\Helper\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\UmSMSNotification\Helper\UltimateMemberSMSNotificationMessages;
use OTP\Notifications\UmSMSNotification\Helper\UltimateMemberSMSNotificationUtility;
use OTP\Helper\MoUtility;
use OTP\Objects\SMSNotification;

/**
 * This class is used to handle all the settings and function related
 * to the UltimateMember New User Admin SMS Notification. It initializes the
 * notification related settings and implements the functionality for
 * sending the SMS to the user.
 */
if ( ! class_exists( 'UltimateMemberNewUserAdminNotification' ) ) {
	/**
	 * UltimateMemberNewUserAdminNotification class
	 */
	class UltimateMemberNewUserAdminNotification extends SMSNotification {

		/**
		 * Instance.
		 *
		 * @var mixed $insatance Instance.
		 */
		public static $instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			parent::__construct();
			$this->title             = 'New Account';
			$this->page              = 'um_new_user_admin_notif';
			$this->is_enabled        = false;
			$this->tool_tip_header   = 'NEW_UM_CUSTOMER_NOTIF_HEADER';
			$this->tool_tip_body     = 'NEW_UM_CUSTOMER_ADMIN_NOTIF_BODY';
			$this->recipient         = UltimateMemberSMSNotificationUtility::get_admin_phone_number();
			$this->sms_body          = UltimateMemberSMSNotificationMessages::showMessage(
				UltimateMemberSMSNotificationMessages::NEW_UM_CUSTOMER_ADMIN_SMS
			);
			$this->default_sms_body  = UltimateMemberSMSNotificationMessages::showMessage(
				UltimateMemberSMSNotificationMessages::NEW_UM_CUSTOMER_ADMIN_SMS
			);
			$this->available_tags    = '{site-name},{username},{email},{fullname}';
			$this->page_header       = mo_( 'NEW ACCOUNT ADMIN NOTIFICATION SETTINGS' );
			$this->page_description  = mo_( 'SMS notifications settings for New Account creation SMS sent to the admins' );
			$this->notification_type = mo_( 'Administrator' );
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
			$phone_numbers = maybe_unserialize( $this->recipient );
			$phone_numbers = is_array( $phone_numbers ) ? $phone_numbers : explode( ';', $phone_numbers );

			$username    = um_user( 'user_login' ); // phpcs::ignore -- Default function of Ultimate Member Plugin.
			$profile_url = um_user_profile_url();// phpcs::ignore -- Default function of Ultimate Member Plugin.
			$full_name   = um_user( 'full_name' );// phpcs::ignore -- Default function of Ultimate Member Plugin.
			$email       = um_user( 'user_email' );// phpcs::ignore -- Default function of Ultimate Member Plugin.

			$replaced_string = array(
				'site-name'       => get_bloginfo(),
				'username'        => $username,
				'accountpage-url' => $profile_url,
				'fullname'        => $full_name,
				'email'           => $email,
			);
			$replaced_string = apply_filters( 'mo_um_new_customer_admin_notif_string_replace', $replaced_string );
			$sms_body        = MoUtility::replace_string( $replaced_string, $this->sms_body );

			if ( MoUtility::is_blank( $phone_numbers ) ) {
				return;
			}
			foreach ( $phone_numbers as $phone_number ) {
				MoUtility::send_phone_notif( $phone_number, $sms_body );
			}
		}
	}
}
