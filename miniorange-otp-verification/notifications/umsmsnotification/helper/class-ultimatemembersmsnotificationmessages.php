<?php
/**
 * Contains all the messages used in Ultimate Member SMS Notifications
 *
 * @package miniorange-otp-verification/Notifications/umsmsnotification/helper
 */

namespace OTP\Notifications\UmSMSNotification\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use OTP\Helper\MoUtility;
use OTP\Objects\BaseMessages;
use OTP\Traits\Instance;

/**
 * This is the constant class which lists all the messages
 * to be shown in the plugin.
 */
if ( ! class_exists( 'UltimateMemberSMSNotificationMessages' ) ) {
	/**
	 * UltimateMemberSMSNotificationMessages class
	 */
	final class UltimateMemberSMSNotificationMessages extends BaseMessages {

		use Instance;
		/**
		 * Initializes values
		 */
		public function __construct() {
			/** Created an array instead of messages instead of constant variables for Translation reasons. */
			define(
				'MO_UM_ADDON_MESSAGES',
				maybe_serialize(
					array(
						self::NEW_UM_CUSTOMER_NOTIF_HEADER => mo_( 'NEW ACCOUNT NOTIFICATION' ),
						self::NEW_UM_CUSTOMER_NOTIF_BODY   => mo_(
							'Customers are sent a new account SMS notification' .
																	' when they sign up on the site.'
						),
						self::NEW_UM_CUSTOMER_SMS          => mo_(
							'Thanks for creating an account on {site-name}. Your username is {username} -miniorange'
						),
						self::NEW_UM_CUSTOMER_ADMIN_NOTIF_BODY => mo_(
							'Admins are sent a new account SMS notification when' .
																			' a user signs up on the site.'
						),
						self::NEW_UM_CUSTOMER_ADMIN_SMS    => mo_(
							'New User Created on {site-name}. Username: {username} -miniorange'
						),
					)
				)
			);
		}



		/**
		 * This function is used to fetch and process the Messages to
		 * be shown to the user. It was created to mostly show dynamic
		 * messages to the user.
		 *
		 * @param string $message_keys   message key or keys.
		 * @param array  $data           key value of the data to be replaced in the message.
		 * @return string
		 */
		public static function showMessage( $message_keys, $data = array() ) {
			$display_message = '';
			$message_keys    = explode( ' ', $message_keys );
			$messages        = maybe_unserialize( MO_UM_ADDON_MESSAGES );
			$common_messages = maybe_unserialize( MO_MESSAGES );
			$messages        = array_merge( $messages, $common_messages );
			foreach ( $message_keys as $message_key ) {
				if ( MoUtility::is_blank( $message_key ) ) {
					return $display_message;
				}
				$format_message = $messages[ $message_key ];
				foreach ( $data as $key => $value ) {
					$format_message = str_replace( '{{' . $key . '}}', $value, $format_message );
				}
				$display_message .= $format_message;
			}
			return $display_message;
		}
	}
}
