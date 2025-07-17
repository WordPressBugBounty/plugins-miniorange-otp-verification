<?php
/**
 * Ultimate Member Notifications List
 *
 * @package miniorange-otp-verification/Notifications/umsmsnotification/helper
 */

namespace OTP\Notifications\UmSMSNotification\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Notifications\UmSMSNotification\Helper\Notifications\UltimateMemberNewCustomerNotification;
use OTP\Notifications\UmSMSNotification\Helper\Notifications\UltimateMemberNewUserAdminNotification;
use OTP\Traits\Instance;

/**
 * This class is used to list down all the Ultimate Member  Notifications and initialize
 * each of the Notification classes so that it's accessible plugin wide. This
 * class is basically used to handle all the specific Ultimate Member  Notification classes.
 */
if ( ! class_exists( 'UltimateMemberNotificationsList' ) ) {
	/**
	 * UltimateMemberNotificationsList class
	 */
	class UltimateMemberNotificationsList {

		/**
		 * New customer notification class
		 *
		 * @var UltimateMemberNewCustomerNotification
		 */
		public $um_new_customer_notif;

		/**
		 * New User Admin Notification
		 *
		 * @var UltimateMemberNewUserAdminNotification
		 */
		public $um_new_user_admin_notif;

		use Instance;
		/**
		 * Initializes values
		 */
		protected function __construct() {
			$this->um_new_customer_notif   = UltimateMemberNewCustomerNotification::getInstance();
			$this->um_new_user_admin_notif = UltimateMemberNewUserAdminNotification::getInstance();
		}


		/**
		 * Getter function of the $um_new_customer_notif. Returns the instance
		 * of the UltimateMemberNewCustomerNotification class.
		 */
		public function get_um_new_customer_notif() {
			return $this->um_new_customer_notif;
		}


		/**
		 * Getter function of the $um_new_user_admin_notif. Returns the instance
		 * of the UltimateMemberNewUserAdminNotification class.
		 */
		public function get_um_new_user_admin_notif() {
			return $this->um_new_user_admin_notif;
		}

	}
}
