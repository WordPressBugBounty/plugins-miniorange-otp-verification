<?php
/**Load adminstrator changes for MoPHPSessions
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

use OTP\Objects\IMoSessions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** TODO: Need to move each session type to different files */
if ( ! class_exists( 'MoPHPSessions' ) ) {
	/**
	 * MoPHPSessions class
	 */
	class MoPHPSessions implements IMoSessions {

		/**
		 * Sets session values.
		 *
		 * @param string $key key value.
		 * @param mixed  $val value of key pair.
		 */
		public static function add_session_var( $key, $val ) {
			switch ( MOV_SESSION_TYPE ) {
				case 'COOKIE':
					setcookie( $key, maybe_serialize( $val ) );
					break;
				case 'SESSION':
					self::check_session();
					$_SESSION[ $key ] = maybe_serialize( $val );
					break;
				case 'CACHE':
					if ( ! wp_cache_add( $key, maybe_serialize( $val ) ) ) {
						wp_cache_replace( $key, maybe_serialize( $val ) );
					}
					break;
				case 'TRANSIENT':
					if ( ! isset( $_COOKIE['transient_key'] ) ) { //phpcs:ignore -- false positive.
						if ( ! wp_cache_get( 'transient_key' ) ) {
							$transient_key = MoUtility::rand();
							if ( ob_get_contents() ) {
								ob_clean();
							}
							setcookie( 'transient_key', $transient_key, time() + 12 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
							wp_cache_add( 'transient_key', $transient_key );
						} else {
							$transient_key = wp_cache_get( 'transient_key' );
						}
					} else {
						$transient_key = sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ); //phpcs:ignore -- false positive.
					}
					set_site_transient( $transient_key . $key, $val, 12 * HOUR_IN_SECONDS );
					break;

			}
		}

		/**
		 * Return the value stored in session.
		 *
		 * @param string $key    - key against the value is stored.
		 * @return mixed
		 */
		public static function get_session_var( $key ) {
			switch ( MOV_SESSION_TYPE ) {
				case 'COOKIE':
					return maybe_unserialize( isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : null ); //phpcs:ignore -- false positive.
				case 'SESSION':
					self::check_session();
					return maybe_unserialize( MoUtility::sanitize_check( $key, $_SESSION ) );
				case 'CACHE':
					return maybe_unserialize( wp_cache_get( $key ) );
				case 'TRANSIENT':
					$transient_key = isset( $_COOKIE['transient_key'] ) //phpcs:ignore -- false positive.
					? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' ); //phpcs:ignore -- false positive.
					return get_site_transient( $transient_key . $key );
			}
		}

		/**
		 * Unsets the session values as per the type set for.
		 *
		 * @param string $key       -   key to unset.
		 */
		public static function unset_session( $key ) {
			switch ( MOV_SESSION_TYPE ) {
				case 'COOKIE':
					unset( $_COOKIE[ $key ] ); //phpcs:ignore -- false positive.
					setcookie( $key, '', time() - ( 15 * 60 ) );
					break;
				case 'SESSION':
					self::check_session();
					unset( $_SESSION[ $key ] );
					break;
				case 'CACHE':
					wp_cache_delete( $key );
					break;
				case 'TRANSIENT':
					$transient_key = isset( $_COOKIE['transient_key'] ) //phpcs:ignore -- false positive.
					? sanitize_text_field( wp_unslash( $_COOKIE['transient_key'] ) ) : wp_cache_get( 'transient_key' ); //phpcs:ignore -- false positive.
					if ( ! MoUtility::is_blank( $transient_key ) ) {
						delete_site_transient( $transient_key . $key );
					}
					break;
			}
		}

		/**
		 * Checks if session started or not. Initiates session of not already initialized.
		 */
		public static function check_session() {
			if ( 'SESSION' === MOV_SESSION_TYPE ) {
				if ( session_id() === '' || ! isset( $_SESSION ) ) {
					session_start();
				}
			}
		}
	}
}
