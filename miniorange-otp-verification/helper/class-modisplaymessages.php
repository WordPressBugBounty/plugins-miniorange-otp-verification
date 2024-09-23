<?php
/**Load adminstrator changes for MoDisplayMessages
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This function is used to DisplayMessages in WordPress. You
 * can decide the HTML code to show your message based on the
 * type of the message you want to show.
 */
if ( ! class_exists( 'MoDisplayMessages' ) ) {
	/**
	 * MoDisplayMessages class
	 */
	class MoDisplayMessages {
		/**Global Variable
		 *
		 * @var message to show
		 */
		private $message;
		/**Global Variable
		 *
		 * @var type of message
		 */
		private $type;

		/**Constructor to declare variables of the class on initialization
		 *
		 * @param string $message message to show.
		 * @param string $type type of message to show.
		 **/
		public function __construct( $message, $type ) {
			$this->message = $message;
			$this->type    = $type;

			add_action( 'admin_notices', array( $this, 'render' ) );
		}
		/**Function to render
		 */
		public function render() {
			switch ( $this->type ) {
				case 'CUSTOM_MESSAGE':
					echo esc_html( mo_( $this->message ) );
					break;
				case 'NOTICE':
					echo '<div style="margin-top:1%;"' .
					'class="is-dismissible notice notice-warning mo-admin-notif">' .
					'<p>' . wp_kses( mo_( $this->message ), MoUtility::mo_allow_html_array() ) . '</p>' .
					'</div>';
					break;
				case 'ERROR':
					echo '<div style="margin-top:1%;"' .
					'class="notice mo-notice-error notice-error is-dismissible mo-admin-notif">' .
					'<p>' . wp_kses( mo_( $this->message ), MoUtility::mo_allow_html_array() ) . '</p>' .
					'</div>';
					break;
				case 'SUCCESS':
					echo '<div  style="margin-top:1%;"' .
					'class="notice mo-notice-success notice-success is-dismissible mo-admin-notif">' .
					'<p>' . wp_kses( mo_( $this->message ), MoUtility::mo_allow_html_array() ) . '</p>' .
					'</div>';
					break;
			}
		}

		/**Function for styling of message divisions
		 */
		public function show_message_div_addons() {
			switch ( $this->type ) {
				case 'MO_ADDON_MESSAGE_CUSTOM_MESSAGE_SUCCESS':
					echo '<div  style="margin-top:1%;"' .
					'class="notice mo-notice-success notice-success is-dismissible mo-admin-notif">' .
					'<p>' . wp_kses( mo_( $this->message ), array( 'p' => array() ) ) . '</p>' .
					'</div>';
					break;
				case 'MO_ADDON_MESSAGE_CUSTOM_MESSAGE_ERROR':
					echo '<div style="margin-top:1%;"' .
					'class="notice mo-notice-error notice-error is-dismissible mo-admin-notif">' .
					'<p>' . wp_kses( mo_( $this->message ), array( 'p' => array() ) ) . '</p>' .
					'</div>';
					break;
			}
		}
	}
}
