<?php
/**Load Interface NotificationSettings
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'NotificationSettings' ) ) {
	/**
	 *  This class is used to generate notification settings
	 *  specific to email or sms settings. These settings are then passed
	 *  to the cURL function to send notifications.
	 */
	class NotificationSettings {

		/**Varibale declaration
		 *
		 * @var string
		 */
		public $send_sms;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $send_email;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $phone_number;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $from_email;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $from_name;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $to_email;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $to_name;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $subject;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $bcc_email;
		/**Varibale declaration
		 *
		 * @var string
		 */
		public $message;

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( func_num_args() < 4 ) {
				$this->create_sms_notification_settings( func_get_arg( 0 ), func_get_arg( 1 ) );
			} else {
				$this->create_email_notification_settings(
					func_get_arg( 0 ),
					func_get_arg( 1 ),
					func_get_arg( 2 ),
					func_get_arg( 3 ),
					func_get_arg( 4 )
				);
			}
		}

		/**
		 * Create Phone notification settings
		 *
		 * @param  string $phone_number phone number.
		 * @param  string $message message to send.
		 */
		public function create_sms_notification_settings( $phone_number, $message ) {
			$this->send_sms     = true;
			$this->phone_number = $phone_number;
			$this->message      = $message;
		}


		/**
		 * Create Email notification settings
		 *
		 * @param string $from_email from email param.
		 * @param string $from_name from name param.
		 * @param string $to_email to email param.
		 * @param string $subject subject of notification.
		 * @param string $message message content.
		 */
		public function create_email_notification_settings( $from_email, $from_name, $to_email, $subject, $message ) {
			$this->send_email = true;
			$this->from_email = $from_email;
			$this->from_name  = $from_name;
			$this->to_email   = $to_email;
			$this->to_name    = $to_email;
			$this->subject    = $subject;
			$this->bcc_email  = '';
			$this->message    = $message;
		}
	}
}
