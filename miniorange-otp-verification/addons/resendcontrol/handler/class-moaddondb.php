<?php
/**
 *  Limit OTP DB Handler.
 *
 * @package miniorange-otp-verification/addons
 */

namespace ROC\Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\MoPHPSessions;
use ROC\Traits\Instance;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * This class handles the DB Calls.
 */
if ( ! class_exists( 'MoAddonDb' ) ) {
	/**
	 * MoAddonDb class
	 */
	class MoAddonDb {

			use Instance;

		/**
		 *  User limit variable.
		 *
		 * @var mixed $user_limit_table
		 */
		private $user_limit_table;
		/**
		 * Intializes the values
		 */
		public function __construct() {
			global $wpdb;
			if ( ! get_mo_rc_option( 'otp_control_enable' ) ) {
				return;
			}
			$this->user_limit_table = $wpdb->prefix . 'mo_otp_control_details';
			$this->generate_tables();

		}

		/**
		 * Generates the required tables if they don't exist.
		 */
		public function generate_tables() {
			global $wpdb;

			if ( $wpdb->get_var( "show tables like '$this->user_limit_table'" ) !== $this->user_limit_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
				$table_name = $this->user_limit_table;
				$sql        = 'CREATE TABLE ' . $table_name . ' (
				`id` bigint NOT NULL auto_increment,
				`user_name` mediumtext NOT NULL, 
				`user_ip` mediumtext NOT NULL,
				`user_phone` mediumtext NOT NULL,
				`user_email` mediumtext NOT NULL,
				`resend_attempts` tinyint DEFAULT 0,
				`login_otp_attempts` tinyint DEFAULT 0,
				`is_blocked` tinyint DEFAULT 0,
				`blocking_timestamp` mediumtext, 
				UNIQUE KEY id (id) );';
				dbDelta( $sql );
			}
		}


		/**
		 * Inserts a new user into the database.
		 *
		 * @param string $user_login    The user's login name.
		 * @param string $user_ip    The user's IP address.
		 * @param string $user_email  The user's email address.
		 * @param string $phone_number       The user's phone number.
		 * @return bool                 True on success, false on failure.
		 */
		public function insert_user( $user_login, $user_ip, $user_email = null, $phone_number = null ) {
			global $wpdb;
			$sql  = "INSERT INTO $this->user_limit_table (user_name, user_ip,user_phone,user_email, resend_attempts, login_otp_attempts,is_blocked) VALUES ('$user_login','$user_ip','$phone_number', '$user_email', 0,0,0)";
			$flag = $wpdb->query( $sql );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
			return $flag;
		}

		/**
		 * Checks if a user already exists in the database.
		 *
		 * @param string $user_name     The user's login name.
		 * @param string $user_email    The user's email address.
		 * @param string $phone_number  The user's phone number.
		 * @param string $user_ip       The user's IP address.
		 * @param string $otp_type       The type of OTP (either 'email' or 'phone').
		 * @return int|null             The user ID if found, null otherwise.
		 */
		public function check_if_user_exists( $user_name, $user_email, $phone_number, $user_ip, $otp_type ) {
			global $wpdb;
			$to_search = 'email' === $otp_type ? $user_email : $phone_number;
			$sql       = "SELECT id FROM $this->user_limit_table WHERE user_name = '" . $user_name . "' and (user_email = '" . $to_search . "' or user_phone='" . $to_search . "') and user_ip = '" . $user_ip . "'";
			$id        = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
			return $id;
		}


		/**
		 * Retrieves the number of OTP resend attempts for a user.
		 *
		 * @param int $user_id  The user ID.
		 * @return int          The number of resend attempts.
		 */
		public function check_otp_resend_attempts( $user_id ) {
			global $wpdb;
			$sql      = "SELECT resend_attempts FROM  $this->user_limit_table WHERE id = '" . $user_id . "'";
			$attempts = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.		
			return $attempts;
		}

		/**
		 * Updates the number of resend attempts for a user.
		 *
		 * @param int $user_id    The user ID.
		 * @param int $attempts   The new number of resend attempts.
		 *
		 * @return void
		 */
		public function update_attempt( $user_id, $attempts ) {
			global $wpdb;
			$sql = 'UPDATE ' . $this->user_limit_table . " SET resend_attempts = '" . $attempts . "' where id= '" . $user_id . "'";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
		}

		/**
		 * Blocks or unblocks a user.
		 *
		 * @param int    $user_id    The user ID.
		 * @param int    $is_blocked Whether to block (1) or unblock (0) the user.
		 * @param string $timestamp  The blocking timestamp.
		 *
		 * @return void
		 */
		public function mo_block_unblock_user( $user_id, $is_blocked, $timestamp ) {
			global $wpdb;
			$sql = 'UPDATE ' . $this->user_limit_table . ' SET is_blocked = ' . $is_blocked . " , blocking_timestamp = '" . $timestamp . "' WHERE id = '" . $user_id . "'";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
		}

		/**
		 * Blocks or unblocks a user.
		 *
		 * @param int    $user_id    The user ID.
		 * @param int    $is_blocked Whether to block (1) or unblock (0) the user.
		 * @param string $timestamp  The blocking timestamp.
		 *
		 * @return void
		 */
		public function mo_unblock_user( $user_id, $is_blocked, $timestamp ) {
			global $wpdb;

			$sql = 'DELETE FROM ' . $this->user_limit_table . " WHERE id = '" . $user_id . "'";
			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
		}

		/**
		 * Checks if a user is blocked.
		 *
		 * @param int $user_id  The user ID.
		 * @return int          1 if blocked, 0 otherwise.
		 */
		public function is_blocked( $user_id ) {
			global $wpdb;
			$sql        = "SELECT is_blocked FROM  $this->user_limit_table WHERE id = '" . $user_id . "'";
			$is_blocked = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
			return $is_blocked;
		}

		/**
		 * Checks the blocking timestamp for a user.
		 *
		 * @param int $user_id  The user ID.
		 * @return string|null  The blocking timestamp if found, null otherwise.
		 */
		public function mo_check_time_and_update_user( $user_id ) {
			global $wpdb;
			$sql    = "SELECT blocking_timestamp FROM $this->user_limit_table WHERE id = '" . $user_id . "'";
			$result = $wpdb->get_var( $sql );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared, Direct database call without caching detected -- DB Direct Query is necessary here.
			return $result;
		}
	}

}
