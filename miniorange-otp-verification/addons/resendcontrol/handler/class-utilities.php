<?php
/**
 * Limit OTP Utilities to handle DB calls
 *
 * @package miniorange-otp-verification/addons
 */

namespace ROC\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ROC\Traits\Instance;
/**
 * This class lists out all the messages that can be used across the AddOn.
 * Created a Base Class to handle all messages.
 */
if ( ! class_exists( 'Utilities' ) ) {
	/**
	 * Utilities class
	 */
	class Utilities {

		use Instance;

		/**Checks the number of OTP resend attempts for a user.
		 *
		 * @param int $user_id The ID of the user.
		 * @return int The number of resend attempts.
		 */
		public function check_attempts( $user_id ) {
			$mo_otp_limit_db = new MoAddonDb();
			$attempts        = $mo_otp_limit_db->check_otp_resend_attempts( $user_id );
			return $attempts;
		}
		/**
		 * Updates the number of attempts for a user.
		 *
		 * @param int $user_id   The ID of the user.
		 * @param int $attempts  The updated number of attempts.
		 *
		 * @return void
		 */
		public function mo_update_attempts( $user_id, $attempts ) {
			$mo_otp_limit_db = new MoAddonDb();
			$mo_otp_limit_db->update_attempt( $user_id, $attempts );
		}

		/**
		 * Inserts a new user into the database.
		 *
		 * @param string $user_login    The username of the user.
		 * @param string $user_email    The email address of the user.
		 * @param string $phone_number  The phone number of the user.
		 * @param string $user_ip        The IP address of the user.
		 *
		 * @return bool True if the user is successfully inserted, false otherwise.
		 */
		public function mo_insert_user( $user_login, $user_email, $phone_number, $user_ip ) {
			$mo_otp_limit_db = new MoAddonDb();
			$flag            = $mo_otp_limit_db->insert_user( $user_login, $user_ip, $user_email, $phone_number );
			return $flag;

		}
		/**
		 * Blocks or unblocks a user.
		 *
		 * @param int    $user_id    The ID of the user.
		 * @param bool   $is_blocked Whether to block or unblock the user.
		 * @param string $timestamp  The timestamp of the block action.
		 *
		 * @return void
		 */
		public function mo_block_unblock_user( $user_id, $is_blocked, $timestamp ) {
			$mo_otp_limit_db = new MoAddonDb();
			$mo_otp_limit_db->mo_block_unblock_user( $user_id, $is_blocked, $timestamp );

		}

		/**
		 * Unblocks a user.
		 *
		 * @param int    $user_id    The ID of the user.
		 * @param bool   $is_blocked Whether to block or unblock the user.
		 * @param string $timestamp  The timestamp of the block action.
		 *
		 * @return void
		 */
		public function mo_unblock_user( $user_id, $is_blocked, $timestamp ) {
			$mo_otp_limit_db = new MoAddonDb();
			$mo_otp_limit_db->mo_unblock_user( $user_id, $is_blocked, $timestamp );
		}

		/**
		 * Checks if a user is blocked.
		 *
		 * @param int $user_id The ID of the user.
		 *
		 * @return bool True if the user is blocked, false otherwise.
		 */
		public function is_blocked( $user_id ) {
			$mo_otp_limit_db = new MoAddonDb();
			$flag            = $mo_otp_limit_db->is_blocked( $user_id );
			return $flag;
		}

		/**
		 * Retrieves the client's IP address.
		 *
		 * @return string|null The client's IP address, or null if not available.
		 */
		public function get_client_ipaddress() {
			$ip = null;
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ip = isset( $_SERVER['HTTP_CLIENT_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) : '';
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';

			} else {
				$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			}
			return $ip;
		}

		/**
		 * Checks the current time and updates the user's status based on the OTP control block time.
		 * check current time for particular id according to otp_block_time

		 * @param int $user_id               The ID of the user.
		 * @param int $current_timestamp     The current timestamp.
		 * @param int $otp_control_block_time The OTP control block time in minutes.
		 *
		 * @return string 'renew' if the user's status is renewed, 'blocked' if the user is still blocked.
		 */
		public function mo_check_time_and_update_user( $user_id, $current_timestamp, $otp_control_block_time ) {

			$mo_otp_limit_db    = new MoAddonDb();
			$user_blocking_time = $mo_otp_limit_db->mo_check_time_and_update_user( $user_id );

			$utc_enoch_control_block_time = (int) $user_blocking_time + ( (int) $otp_control_block_time * 60 );

			if ( $utc_enoch_control_block_time < $current_timestamp ) {
				$is_blocked = 0;
				$this->mo_unblock_user( $user_id, $is_blocked, null );
				$this->mo_update_attempts( $user_id, 0 );
				return 'renew';
			} else {
				return 'blocked';
			}

		}



	}
}
