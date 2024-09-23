<?php
/**Load administrator changes for MoWhatsApp
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

use OTP\Traits\Instance;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MocURLCall;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class is for Whatsapp Verification and all its functions
 */
if ( ! class_exists( 'MoWhatsApp' ) ) {
	/**
	 * MoWhatsApp class
	 */
	class MoWhatsApp {

		use Instance;
		/**Constructor
		 **/
		protected function __construct() {
			add_filter( 'mo_wa_send_otp_token', array( $this, 'mo_wp_send_test_notif' ), 99, 4 );
		}

		/**
		 * Function to send the test WhatsApp OTP/ Notifications.
		 *
		 * @param string $auth_type  OTP Type - EMAIL or SMS.
		 * @param string $email     Email Address of the user.
		 * @param string $phone     Phone Number of the user.
		 * @param array  $data     Data of the user.
		 * @return array
		 */
		public function mo_wp_send_test_notif( $auth_type, $email, $phone, $data ) {
			$content = $this->send_test_whatsapp_notification( $phone, $data );
			$message = json_decode( $content )->message;
			if ( isset( $message ) ) {
				return wp_json_encode( $message );
			} else {
				return $content;
			}
		}

		/**
		 * Function to check the user details provided and send the Test WhatsApp OTP/ Notification.
		 *
		 * @param string $phone Phone Number of the user.
		 * @param array  $data Data of the user.
		 * @return array
		 */
		public function send_test_whatsapp_notification( $phone, $data ) {

			if ( isset( $data['action'] ) && 'wa_miniorange_get_test_response' === ( $data['action'] ) ) {

				$admin_email   = get_mo_option( 'admin_email' );
				$customer_pass = $data['customer_pass'];
				$customer_key  = get_mo_option( 'admin_customer_key' );

				$content          = MocURLCall::get_customer_key( $admin_email, $customer_pass );
				$customer_details = json_decode( $content );

				if ( strval( $customer_details->id ) !== $customer_key ) {
					return $content;
				}
			}

			$response = $this->send_whatsapp_notif( $phone, $customer_key, $admin_email, $customer_pass );
			return $response;
		}

		/**
		 * Calls the server to send test whatsapp SMS to the user's phone.
		 *
		 * @param string $phone     Phone Number of the user.
		 * @param string $customer_key  Customer key of the logged in user.
		 * @param string $admin_email  Admin email.
		 * @param string $customer_pass Customer password.
		 * @return string
		 */
		public function send_whatsapp_notif( $phone, $customer_key, $admin_email, $customer_pass ) {

			$url       = MoConstants::HOSTNAME . '/moas/api/plugin/whatsapp/send';
			$site_name = get_bloginfo( 'name' );

			/*only to send test whatsapp notification.*/
			$message = 'wp_whatsapp_test_config_otp_message';
			$fields  = array(
				'customerId'       => $customer_key,
				'variable'         => array(
					'var1' => $site_name,
				),
				'isDefault'        => true,
				'templateId'       => $message,
				'phoneNumber'      => $phone,
				'templateLanguage' => 'en',
				'customerEmail'    => $admin_email,
				'customerPassword' => $customer_pass,
			);

			$json     = wp_json_encode( $fields );
			$response = MocURLCall::call_api( $url, $json );
			return $response;
		}
	}
}
