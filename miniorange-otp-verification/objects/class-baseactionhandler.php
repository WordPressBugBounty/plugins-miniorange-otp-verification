<?php
/**Load Interface BaseActionHandler
 *
 * @package miniorange-otp-verification/objects
 */

namespace OTP\Objects;

use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BaseActionHandler' ) ) {
	/**
	 * This is the Base class
	 */
	class BaseActionHandler {

		/**Variable declaration
		 *
		 * @var string $nonce nonce value to check if a valid submission has been made */
		protected $nonce;

		/**
		 * Nonce Key Variable declaration
		 *
		 * @var String nonce-key value to check if a valid submission has been made
		 */
		protected $nonce_key;

		/**Constructor
		 **/
		protected function __construct() {}

		/**
		 * Checks if the request made is a valid request or not. The user trying to
		 * make the request should have the manage_options capability set and
		 * the nonce should be valid.
		 * This is to make sure only admins are able to save settings.
		 */
		protected function is_valid_request() {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_attr( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			return true;
		}


		/**
		 * Checks if the request made is a valid ajax request or not.
		 * Only checks the none value for now.
		 *
		 * @param string $key The key which has the nonce value.
		 */
		protected function is_valid_ajax_request( $key ) {
			if ( ! check_ajax_referer( $this->nonce, $key ) ) {
				wp_send_json(
					MoUtility::create_json(
						MoMessages::showMessage( BaseMessages::INVALID_OP ),
						MoConstants::ERROR_JSON_TYPE
					)
				);
			}
		}


		/** Getter for nonce value  */
		public function get_nonce_value() {
			return $this->nonce; }
	}
}
