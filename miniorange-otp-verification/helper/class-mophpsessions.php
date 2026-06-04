<?php
/**
 * Load administrator changes for MoPHPSessions
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Objects\IMoSessions;

/** TODO: Need to move each session type to different files */
if ( ! class_exists( 'MoPHPSessions' ) ) {
	/**
	 * Class for managing different types of session storage mechanisms.
	 *
	 * Implements the IMoSessions interface to provide consistent session handling
	 * across different storage types: PHP sessions, cookies, cache and transients.
	 *
	 * AUTO mode (recommended) picks SESSION or TRANSIENT per browser and falls back
	 * automatically when the primary mechanism cannot read/write OTP state.
	 */
	class MoPHPSessions implements IMoSessions {

		const STORAGE_MODE_COOKIE = 'mo_otp_storage_mode';

		/**
		 * Resolved storage type for the current request (SESSION or TRANSIENT when using AUTO/fallback).
		 *
		 * @var string|null
		 */
		private static $resolved_type = null;

		/**
		 * Whether PHP session viability was probed this request.
		 *
		 * @var bool
		 */
		private static $session_viability_checked = false;

		/**
		 * Cached result of PHP session viability probe.
		 *
		 * @var bool
		 */
		private static $session_viable = false;

		/**
		 * Whether shutdown/REST hooks were registered to close PHP sessions.
		 *
		 * @var bool
		 */
		private static $close_hooks_registered = false;

		/**
		 * Bootstraps storage early so cookies can be set before output (fixes TRANSIENT on cached themes).
		 *
		 * @return void
		 */
		public static function bootstrap() {
			if ( ! defined( 'MOV_SESSION_TYPE' ) ) {
				return;
			}

			self::register_session_close_hooks();

			if ( self::should_cross_fallback() ) {
				self::ensure_transient_cookie();
			}
		}

		/**
		 * Registers hooks so PHP sessions are closed before REST/loopback HTTP requests (Site Health).
		 *
		 * @return void
		 */
		private static function register_session_close_hooks() {
			if ( self::$close_hooks_registered || ! \function_exists( 'add_action' ) ) {
				return;
			}

			self::$close_hooks_registered = true;
			\add_action( 'rest_api_init', array( __CLASS__, 'close_php_session' ), 0 );
			\add_filter( 'pre_http_request', array( __CLASS__, 'close_php_session_before_http' ), 1, 3 );
			\add_action( 'shutdown', array( __CLASS__, 'close_php_session' ), 0 );
		}

		/**
		 * Closes PHP session before WordPress loopback/REST HTTP requests (Site Health).
		 *
		 * @param false|array|\WP_Error $preempt Whether to preempt an HTTP request's return value.
		 * @param array                 $args    HTTP request arguments.
		 * @param string                $url     The request URL.
		 * @return false|array|\WP_Error
		 */
		public static function close_php_session_before_http( $preempt, $args, $url ) {
			unset( $args, $url );
			self::close_php_session();
			return $preempt;
		}

		/**
		 * Sets session values based on the configured session type.
		 *
		 * @param string $key Key to store data under.
		 * @param mixed  $val Value to store.
		 * @return void
		 */
		public static function add_session_var( $key, $val ) {
			if ( empty( $key ) ) {
				return;
			}

			$storage_type = self::get_configured_storage_type();
			if ( self::should_cross_fallback() ) {
				$primary = self::get_effective_storage_type();
				if ( ! self::write_by_type( $primary, $key, $val ) ) {
					$alternate = self::get_alternate_storage_type( $primary );
					if ( self::write_by_type( $alternate, $key, $val ) ) {
						self::persist_storage_preference( $alternate );
					}
				}
				return;
			}

			self::write_by_type( $storage_type, $key, $val );
		}

		/**
		 * Retrieves a value stored in session by key.
		 *
		 * @param string $key Key used to store the value.
		 * @return mixed Value stored under the key, or null if not found.
		 */
		public static function get_session_var( $key ) {
			if ( empty( $key ) ) {
				return;
			}

			if ( self::should_cross_fallback() ) {
				$primary = self::get_effective_storage_type();
				$value   = self::read_by_type( $primary, $key );
				if ( null !== $value ) {
					return $value;
				}
				return self::read_by_type( self::get_alternate_storage_type( $primary ), $key );
			}

			return self::read_by_type( self::get_configured_storage_type(), $key );
		}

		/**
		 * Unsets session values for the specified key.
		 *
		 * @param string $key Key to unset from the session.
		 * @return void
		 */
		public static function unset_session( $key ) {
			if ( self::should_cross_fallback() ) {
				self::unset_by_type( 'SESSION', $key );
				self::unset_by_type( 'TRANSIENT', $key );
				return;
			}

			self::unset_by_type( self::get_configured_storage_type(), $key );
		}

		/**
		 * Legacy no-op: PHP sessions are opened and closed per operation in this class.
		 *
		 * @return void
		 */
		public static function check_session() {
			// Intentionally empty. Use open/close helpers inside MoPHPSessions only.
		}

		/**
		 * Closes an active PHP session so REST API and loopback requests are not blocked.
		 *
		 * @return void
		 */
		public static function close_php_session() {
			if ( \function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === \session_status() ) {
				\session_write_close();
			}
		}

		/**
		 * Opens PHP session for reading and releases the lock when possible.
		 *
		 * @return bool
		 */
		private static function open_php_session_for_read() {
			self::register_session_close_hooks();

			if ( PHP_SESSION_DISABLED === \session_status() ) {
				return false;
			}

			if ( PHP_SESSION_ACTIVE === \session_status() ) {
				return isset( $_SESSION );
			}

			if ( \headers_sent() ) {
				return false;
			}

			if ( \PHP_VERSION_ID >= 70000 ) {
				return @\session_start( array( 'read_and_close' => true ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

			if ( @\session_start() ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return isset( $_SESSION );
			}

			return false;
		}

		/**
		 * Opens PHP session for writing.
		 *
		 * @return bool
		 */
		private static function open_php_session_for_write() {
			self::register_session_close_hooks();

			if ( PHP_SESSION_DISABLED === \session_status() ) {
				return false;
			}

			if ( PHP_SESSION_ACTIVE === \session_status() ) {
				return isset( $_SESSION );
			}

			if ( \headers_sent() ) {
				return false;
			}

			return @\session_start(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		/**
		 * Whether the current request uses native PHP $_SESSION storage.
		 *
		 * @return bool
		 */
		public static function uses_php_session() {
			if ( ! self::should_cross_fallback() ) {
				return 'SESSION' === self::get_configured_storage_type();
			}
			return 'SESSION' === self::get_effective_storage_type();
		}

		/**
		 * Returns configured MOV_SESSION_TYPE (may be AUTO).
		 *
		 * @return string
		 */
		private static function get_configured_storage_type() {
			return defined( 'MOV_SESSION_TYPE' ) ? strtoupper( (string) MOV_SESSION_TYPE ) : 'SESSION';
		}

		/**
		 * SESSION / TRANSIENT / AUTO use adaptive storage with cross-fallback.
		 *
		 * @return bool
		 */
		private static function should_cross_fallback() {
			return in_array( self::get_configured_storage_type(), array( 'SESSION', 'TRANSIENT', 'AUTO' ), true );
		}

		/**
		 * Resolves effective storage (SESSION or TRANSIENT) for this request.
		 *
		 * @return string
		 */
		private static function get_effective_storage_type() {
			if ( null !== self::$resolved_type ) {
				return self::$resolved_type;
			}

			$configured = self::get_configured_storage_type();
			$sticky     = self::get_sticky_storage_mode();

			if ( 'AUTO' === $configured ) {
				self::$resolved_type = $sticky ? $sticky : self::detect_auto_storage_type();
			} elseif ( $sticky ) {
				self::$resolved_type = $sticky;
			} else {
				self::$resolved_type = in_array( $configured, array( 'SESSION', 'TRANSIENT' ), true ) ? $configured : 'TRANSIENT';
			}

			/**
			 * Filter the storage backend used for OTP session data.
			 *
			 * @param string $storage_type SESSION or TRANSIENT.
			 */
			return (string) \apply_filters( 'mo_otp_storage_type', self::$resolved_type );
		}

		/**
		 * Reads sticky per-browser storage preference set after a fallback.
		 *
		 * @return string|null SESSION or TRANSIENT.
		 */
		private static function get_sticky_storage_mode() {
			if ( empty( $_COOKIE[ self::STORAGE_MODE_COOKIE ] ) ) {
				return null;
			}
			$mode = strtoupper( \sanitize_text_field( \wp_unslash( $_COOKIE[ self::STORAGE_MODE_COOKIE ] ) ) );
			return in_array( $mode, array( 'SESSION', 'TRANSIENT' ), true ) ? $mode : null;
		}

		/**
		 * Persists which storage type worked for this browser (12 hours).
		 *
		 * @param string $storage_type SESSION or TRANSIENT.
		 * @return void
		 */
		private static function persist_storage_preference( $storage_type ) {
			$storage_type = strtoupper( (string) $storage_type );
			if ( ! in_array( $storage_type, array( 'SESSION', 'TRANSIENT' ), true ) || \headers_sent() || ! self::can_set_cookie() ) {
				return;
			}

			self::$resolved_type = $storage_type;
			\setcookie( self::STORAGE_MODE_COOKIE, $storage_type, time() + ( 12 * \HOUR_IN_SECONDS ), \COOKIEPATH, \COOKIE_DOMAIN, \is_ssl(), true );
			$_COOKIE[ self::STORAGE_MODE_COOKIE ] = $storage_type;
		}

		/**
		 * Picks a default storage type when MOV_SESSION_TYPE is AUTO.
		 *
		 * @return string
		 */
		private static function detect_auto_storage_type() {
			$session_ok   = self::can_use_session_storage();
			$transient_ok = self::can_use_transient_storage();

			if ( $session_ok && ! $transient_ok ) {
				return 'SESSION';
			}
			if ( $transient_ok && ! $session_ok ) {
				return 'TRANSIENT';
			}
			if ( $session_ok && $transient_ok ) {
				/**
				 * Preferred storage when both SESSION and TRANSIENT are viable (AUTO mode).
				 *
				 * @param string $preferred Default TRANSIENT for WordPress hosting compatibility.
				 */
				return (string) \apply_filters( 'mo_otp_preferred_storage_type', 'TRANSIENT' );
			}

			return 'TRANSIENT';
		}

		/**
		 * Returns the other OTP storage backend.
		 *
		 * @param string $primary SESSION or TRANSIENT.
		 * @return string
		 */
		private static function get_alternate_storage_type( $primary ) {
			return 'SESSION' === $primary ? 'TRANSIENT' : 'SESSION';
		}

		/**
		 * Whether native PHP sessions can be used on this request.
		 *
		 * @return bool
		 */
		private static function can_use_session_storage() {
			if ( self::$session_viability_checked ) {
				return self::$session_viable;
			}

			self::$session_viability_checked = true;
			self::$session_viable            = false;

			if ( PHP_SESSION_DISABLED === session_status() ) {
				return false;
			}

			if ( \headers_sent() && PHP_SESSION_ACTIVE !== session_status() && '' === session_id() ) {
				return false;
			}

			if ( PHP_SESSION_ACTIVE === session_status() || '' !== session_id() ) {
				self::$session_viable = isset( $_SESSION );
				return self::$session_viable;
			}

			if ( \headers_sent() ) {
				return false;
			}

			if ( self::open_php_session_for_write() ) {
				$probe_key              = 'mo_otp_storage_probe';
				$_SESSION[ $probe_key ] = '1';
				self::$session_viable   = isset( $_SESSION[ $probe_key ] );
				unset( $_SESSION[ $probe_key ] );
				self::close_php_session();
			}

			return self::$session_viable;
		}

		/**
		 * Whether WordPress transients (with cookie key) can be used on this request.
		 *
		 * @return bool
		 */
		private static function can_use_transient_storage() {
			if ( ! empty( $_COOKIE['transient_key'] ) ) {
				return true;
			}

			return ! \headers_sent() && self::can_set_cookie();
		}

		/**
		 * Ensures transient_key cookie exists for the current request.
		 *
		 * @return void
		 */
		private static function ensure_transient_cookie() {
			self::get_transient_key();
		}

		/**
		 * Returns the transient storage cookie value, creating it when possible.
		 *
		 * @return string|null
		 */
		private static function get_transient_key() {
			if ( ! empty( $_COOKIE['transient_key'] ) ) {
				return \sanitize_text_field( \wp_unslash( $_COOKIE['transient_key'] ) );
			}

			if ( \headers_sent() || ! self::can_set_cookie() ) {
				return null;
			}

			$transient_key = self::generate_transient_key();
			if ( ! $transient_key ) {
				return null;
			}

			$_COOKIE['transient_key'] = $transient_key;
			\setcookie( 'transient_key', $transient_key, time() + ( 12 * \HOUR_IN_SECONDS ), \COOKIEPATH, \COOKIE_DOMAIN, \is_ssl(), true );

			return $transient_key;
		}

		/**
		 * Whether WordPress cookie constants are available for setcookie().
		 *
		 * @return bool
		 */
		private static function can_set_cookie() {
			return defined( 'COOKIEPATH' ) && defined( 'COOKIE_DOMAIN' );
		}

		/**
		 * Generates a random transient cookie key (safe before pluggable.php is loaded).
		 *
		 * @return string|null
		 */
		private static function generate_transient_key() {
			if ( \function_exists( 'wp_generate_password' ) ) {
				return \wp_generate_password( 32, false );
			}

			if ( \function_exists( 'random_bytes' ) ) {
				return \bin2hex( \random_bytes( 16 ) );
			}

			return \md5( \uniqid( 'mo_otp', true ) );
		}

		/**
		 * Writes OTP state to a specific storage backend.
		 *
		 * @param string $storage_type Storage backend.
		 * @param string $key          Session key.
		 * @param mixed  $val          Value to store.
		 * @return bool
		 */
		private static function write_by_type( $storage_type, $key, $val ) {
			switch ( $storage_type ) {
				case 'SESSION':
					if ( ! self::open_php_session_for_write() || ! isset( $_SESSION ) ) {
						return false;
					}
					$_SESSION[ $key ] = \maybe_serialize( $val );
					$written          = \array_key_exists( $key, $_SESSION );
					self::close_php_session();
					return $written;
				case 'TRANSIENT':
					$transient_key = self::get_transient_key();
					if ( ! $transient_key ) {
						return false;
					}
					return false !== \set_site_transient( 'mo_otp_' . $transient_key . $key, $val, 12 * \HOUR_IN_SECONDS );
				case 'COOKIE':
					if ( \headers_sent() ) {
						return false;
					}
					$cookie_val = \wp_json_encode( $val, JSON_UNESCAPED_SLASHES );
					\setcookie( $key, $cookie_val, time() + ( 12 * \HOUR_IN_SECONDS ), \COOKIEPATH, \COOKIE_DOMAIN, \is_ssl(), true );
					$_COOKIE[ $key ] = $cookie_val;
					return true;
				case 'CACHE':
					if ( ! \wp_cache_add( $key, \maybe_serialize( $val ) ) ) {
						\wp_cache_replace( $key, \maybe_serialize( $val ) );
					}
					return false !== \wp_cache_get( $key );
			}

			return false;
		}

		/**
		 * Reads OTP state from a specific storage backend.
		 *
		 * @param string $storage_type Storage backend.
		 * @param string $key          Session key.
		 * @return mixed|null
		 */
		private static function read_by_type( $storage_type, $key ) {
			switch ( $storage_type ) {
				case 'SESSION':
					if ( ! self::open_php_session_for_read() || ! isset( $_SESSION ) ) {
						return null;
					}
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Internal session payload written by this plugin.
					$raw = isset( $_SESSION[ $key ] ) ? $_SESSION[ $key ] : null;
					if ( \function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === \session_status() ) {
						self::close_php_session();
					}
					if ( null === $raw ) {
						return null;
					}
					return \maybe_unserialize( $raw );
				case 'TRANSIENT':
					if ( empty( $_COOKIE['transient_key'] ) ) {
						return null;
					}
					$transient_key = \sanitize_text_field( \wp_unslash( $_COOKIE['transient_key'] ) );
					$value         = \get_site_transient( 'mo_otp_' . $transient_key . $key );
					return ( false === $value ) ? null : $value;
				case 'COOKIE':
					$raw = isset( $_COOKIE[ $key ] ) ? \sanitize_text_field( \wp_unslash( $_COOKIE[ $key ] ) ) : null;
					if ( null === $raw ) {
						return null;
					}
					$decoded = json_decode( $raw, true );
					if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
						return null;
					}
					return $decoded;
				case 'CACHE':
					$raw = \wp_cache_get( $key );
					if ( null === $raw ) {
						return null;
					}
					return \maybe_unserialize( $raw );
			}

			return null;
		}

		/**
		 * Removes OTP state from a specific storage backend.
		 *
		 * @param string $storage_type Storage backend.
		 * @param string $key          Session key.
		 * @return void
		 */
		private static function unset_by_type( $storage_type, $key ) {
			switch ( $storage_type ) {
				case 'SESSION':
					if ( self::open_php_session_for_write() && isset( $_SESSION[ $key ] ) ) {
						unset( $_SESSION[ $key ] );
					}
					self::close_php_session();
					break;
				case 'TRANSIENT':
					if ( ! empty( $_COOKIE['transient_key'] ) ) {
						$transient_key = \sanitize_text_field( \wp_unslash( $_COOKIE['transient_key'] ) );
						\delete_site_transient( 'mo_otp_' . $transient_key . $key );
					}
					break;
				case 'COOKIE':
					unset( $_COOKIE[ $key ] );
					if ( ! \headers_sent() ) {
						\setcookie( $key, '', time() - ( 15 * 60 ), \COOKIEPATH, \COOKIE_DOMAIN, \is_ssl(), true );
					}
					break;
				case 'CACHE':
					\wp_cache_delete( $key );
					break;
			}
		}
	}
}
