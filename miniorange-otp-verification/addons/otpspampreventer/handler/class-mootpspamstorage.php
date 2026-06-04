<?php
/**
 * OTP Spam Storage Handler
 *
 * @package otpspampreventer/handler
 */

namespace OSP\Handler;

use OSP\Traits\Instance;
use OSP\Helper\MoSecurityHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoOtpSpamStorage' ) ) {
	/**
	 * The class handles storage and retrieval of spam prevention data.
	 * Uses WordPress options table to store hashed keys and attempt data.
	 */
	class MoOtpSpamStorage {

		use Instance;

		/**
		 * Option name prefix for spam data
		 */
		const SPAM_DATA_PREFIX = 'mo_osp_spam_data_';

		/**
		 * Option name for global settings
		 */
		const SETTINGS_OPTION = 'mo_osp_settings';

		/**
		 * Maximum number of entries to keep in storage
		 */
		const MAX_ENTRIES = 10000;

		/**
		 * Constructor
		 */
		public function __construct() {
			// Schedule cleanup hook.
			if ( ! wp_next_scheduled( 'mo_osp_cleanup_expired' ) ) {
				wp_schedule_event( time(), 'hourly', 'mo_osp_cleanup_expired' );
			}
			add_action( 'mo_osp_cleanup_expired', array( $this, 'mosp_cleanup_expired_entries' ) );
		}

		/**
		 * Generate a secure hash for storing identifiers.
		 *
		 * @param string $value The value to hash (phone/email/ip/browser_id).
		 * @return string
		 */
		public function mosp_hash_key( $value ) {
			return hash( 'sha256', strtolower( trim( (string) $value ) ) );
		}

		/**
		 * Get spam data for a given key.
		 *
		 * @param string $key The hashed key.
		 * @return array|false
		 */
		public function mosp_get_spam_data( $key ) {
			$option_name = self::SPAM_DATA_PREFIX . $key;
			$data        = get_mo_option( $option_name );
			if ( false === $data ) {
				return false;
			}

			if ( is_string( $data ) ) {
				$data = maybe_unserialize( $data );
			}

			if ( ! is_array( $data ) ) {
				return false;
			}

			if ( isset( $data['attempts'] ) && ! is_array( $data['attempts'] ) ) {
				$data['attempts'] = array();
			} elseif ( ! isset( $data['attempts'] ) ) {
				$data['attempts'] = array();
			}

			return $data;
		}

		/**
		 * Update spam data for a given key.
		 *
		 * @param string $key The hashed key.
		 * @param array  $data The spam data.
		 * @return bool
		 */
		public function mosp_update_spam_data( $key, $data ) {
			$option_name = self::SPAM_DATA_PREFIX . $key;

			update_mo_option( $option_name, maybe_serialize( $data ) );

			$saved_data = $this->mosp_get_spam_data( $key );

			$success = false;
			if ( false !== $saved_data && is_array( $saved_data ) ) {
				$key_fields_match = true;
				if ( isset( $data['blocked_until'] ) ) {
					$key_fields_match = $key_fields_match && ( isset( $saved_data['blocked_until'] ) && (int) $saved_data['blocked_until'] === (int) $data['blocked_until'] );
				}
				if ( isset( $data['block_reason'] ) ) {
					$key_fields_match = $key_fields_match && ( isset( $saved_data['block_reason'] ) && $saved_data['block_reason'] === $data['block_reason'] );
				}
				$success = $key_fields_match;
			}

			return $success;
		}

		/**
		 * Delete spam data for a given key.
		 *
		 * @param string $key The hashed key.
		 * @return bool|void
		 */
		public function mosp_delete_spam_data( $key ) {
			$option_name = self::SPAM_DATA_PREFIX . $key;
			wp_cache_delete( $option_name, 'mo_osp' );
			return delete_mo_option( $option_name );
		}

		/**
		 * Cached settings.
		 *
		 * @var array|null
		 */
		private static $cached_settings = null;

		/**
		 * Flag to track if settings have been logged (to avoid spam in logs).
		 *
		 * @var bool
		 */
		private static $settings_logged = false;

		/**
		 * Get addon settings.
		 *
		 * @return array
		 */
		public function mosp_get_settings() {
			if ( null !== self::$cached_settings ) {
				return self::$cached_settings;
			}

			$defaults = array(
				'enabled'       => false,
				'cooldown_time' => 60,
				'max_attempts'  => 3,
				'block_time'    => 900,
				'daily_limit'   => 10,
				'hourly_limit'  => 5,
				'track_phone'   => true,
				'track_email'   => true,
				'track_ip'      => true,
				'track_browser' => true,
				'whitelist_ips' => array(),
			);

			$settings = get_mo_option( self::SETTINGS_OPTION );

			if ( false === $settings || ! is_array( $settings ) ) {
				$settings = $defaults;
			} else {
				$settings = wp_parse_args( $settings, $defaults );

				if ( isset( $settings['whitelist_ips'] ) && is_string( $settings['whitelist_ips'] ) ) {
					if ( ! empty( $settings['whitelist_ips'] ) ) {
						$split_by_newline = array_filter( array_map( 'trim', explode( "\n", $settings['whitelist_ips'] ) ) );
						if ( count( $split_by_newline ) === 1 && strpos( $split_by_newline[0], ' ' ) !== false ) {
							$settings['whitelist_ips'] = array_filter( array_map( 'trim', explode( ' ', $settings['whitelist_ips'] ) ) );
						} else {
							$settings['whitelist_ips'] = $split_by_newline;
						}
						$settings['whitelist_ips'] = array_values( $settings['whitelist_ips'] );
					} else {
						$settings['whitelist_ips'] = array();
					}
				} elseif ( isset( $settings['whitelist_ips'] ) && is_array( $settings['whitelist_ips'] ) ) {
					$cleaned_ips = array();
					foreach ( $settings['whitelist_ips'] as $ip_item ) {
						$ip_item = trim( $ip_item );
						if ( empty( $ip_item ) ) {
							continue;
						}
						if ( strpos( $ip_item, ' ' ) !== false ) {
							$split_ips   = array_filter( array_map( 'trim', explode( ' ', $ip_item ) ) );
							$cleaned_ips = array_merge( $cleaned_ips, $split_ips );
						} else {
							$cleaned_ips[] = $ip_item;
						}
					}
					$settings['whitelist_ips'] = array_values( array_unique( $cleaned_ips ) );
				} elseif ( ! isset( $settings['whitelist_ips'] ) || ! is_array( $settings['whitelist_ips'] ) ) {
					$settings['whitelist_ips'] = array();
				}
			}

			self::$cached_settings = $settings;

			if ( ! self::$settings_logged ) {
				self::$settings_logged = true;
			}

			return $settings;
		}

		/**
		 * Update addon settings.
		 *
		 * @param array $settings The settings array.
		 * @return bool
		 */
		public function mosp_update_settings( $settings ) {
			update_mo_option( self::SETTINGS_OPTION, $settings );

			self::$cached_settings = null;

			$saved_settings = get_mo_option( self::SETTINGS_OPTION );
			$success        = ( $saved_settings === $settings );

			return $success;
		}

		/**
		 * Record an OTP attempt.
		 *
		 * @param string $identifier The identifier (phone/email/ip/browser).
		 * @param string $type The type of identifier.
		 * @return array The updated attempt data.
		 */
		public function mosp_record_attempt( $identifier, $type, $context = array() ) {
			$key  = $this->mosp_hash_key( $identifier );
			$data = $this->mosp_get_spam_data( $key );
			$now  = time();

			if ( false === $data ) {
				$data = array(
					'type'          => $type,
					'attempts'      => array(),
					'blocked_until' => 0,
					'total_blocks'  => 0,
					'created'       => $now,
					'last_attempt'  => $now,
				);
			} else {
				if ( ! isset( $data['type'] ) || 'identifier' === $data['type'] || 'unknown' === $data['type'] ) {
					$data['type'] = $type;
				}
				if ( ! isset( $data['identifier'] ) && ! empty( $identifier ) ) {
					if ( strpos( $identifier, 'email:' ) === 0 ) {
						$data['identifier'] = substr( $identifier, 6 );
					} elseif ( strpos( $identifier, 'phone:' ) === 0 ) {
						$data['identifier'] = substr( $identifier, 6 );
					} elseif ( strpos( $identifier, 'ip:' ) === 0 ) {
						$data['identifier'] = substr( $identifier, 3 );
					} elseif ( strpos( $identifier, 'browser:' ) === 0 ) {
						$data['identifier'] = substr( $identifier, 8 );
					}
				}
			}

			if ( is_array( $context ) ) {
				if ( ! empty( $context['ip'] ) && filter_var( $context['ip'], FILTER_VALIDATE_IP ) ) {
					$data['last_ip'] = $context['ip'];
				}
				if ( ! empty( $context['browser_id'] ) ) {
					$data['last_browser'] = $context['browser_id'];
				}
				if ( ! empty( $context['email'] ) ) {
					$data['last_email'] = strtolower( trim( (string) $context['email'] ) );
				}
				if ( ! empty( $context['phone'] ) ) {
					$data['last_phone'] = trim( (string) $context['phone'] );
				}
			}

			$attempts_before = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;

			$data['attempts'][]   = $now;
			$data['last_attempt'] = $now;

			$settings    = $this->mosp_get_settings();
			$time_window = MoSecurityHelper::COUNTING_WINDOW_SECONDS;
			$cutoff_time = $now - $time_window;

			$data['attempts'] = array_filter(
				$data['attempts'],
				function ( $timestamp ) use ( $cutoff_time ) {
					return $timestamp > $cutoff_time;
				}
			);

			$data['attempts'] = array_values( $data['attempts'] );

			$attempts_after = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;

			$this->mosp_update_spam_data( $key, $data );

			return $data;
		}

		/**
		 * Check if an identifier is blocked.
		 *
		 * @param string $identifier The identifier to check.
		 * @return array Block status information.
		 */
		public function mosp_is_blocked( $identifier ) {
			$key      = $this->mosp_hash_key( $identifier );
			$data     = $this->mosp_get_spam_data( $key );
			$settings = $this->mosp_get_settings();
			$now      = time();

			if ( false === $data ) {
				return array(
					'blocked'       => false,
					'reason'        => '',
					'blocked_until' => 0,
					'attempts'      => 0,
				);
			}

			if ( isset( $data['blocked_until'] ) && $data['blocked_until'] > 0 && $data['blocked_until'] <= $now ) {
				$block_reason = isset( $data['block_reason'] ) ? $data['block_reason'] : 'unknown';

				if ( 'max_attempts_exceeded' === $block_reason ) {

					if ( strpos( $identifier, ':' ) !== false ) {
						list( $id_type, $id_value ) = explode( ':', $identifier, 2 );
						$this->mosp_mark_puzzle_required( $id_value );
					} else {
						$this->mosp_mark_puzzle_required( $identifier );
					}
					$attempts_before_clear = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;
					$data['attempts']      = array();
				} else {
					$time_window = MoSecurityHelper::COUNTING_WINDOW_SECONDS;
					$cutoff_time = $now - $time_window;

					$attempts_before_clean = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;

					if ( isset( $data['attempts'] ) && is_array( $data['attempts'] ) ) {
						$data['attempts'] = array_filter(
							$data['attempts'],
							function ( $timestamp ) use ( $cutoff_time ) {
								return $timestamp > $cutoff_time;
							}
						);
						$data['attempts'] = array_values( $data['attempts'] );
					}

					$attempts_after_clean = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;
				}

				$data['blocked_until'] = 0;
				$data['block_reason']  = '';

				$this->mosp_update_spam_data( $this->mosp_hash_key( $identifier ), $data );
			}

			if ( 0 === $data['blocked_until'] && isset( $data['block_count'] ) && $data['block_count'] > 0 ) {
				if ( strpos( $identifier, ':' ) !== false ) {
					list( $id_type, $id_value ) = explode( ':', $identifier, 2 );
					$existing_puzzle            = $this->mosp_is_puzzle_required( $id_value );
				} else {
					$existing_puzzle = $this->mosp_is_puzzle_required( $identifier );
				}

				if ( ! $existing_puzzle ) {
					if ( strpos( $identifier, ':' ) !== false ) {
						list( $id_type, $id_value ) = explode( ':', $identifier, 2 );
						$this->mosp_mark_puzzle_required( $id_value );
					} else {
						$this->mosp_mark_puzzle_required( $identifier );
					}
				}
			}

			if ( $data['blocked_until'] > $now ) {
				$remaining    = $data['blocked_until'] - $now;
				$block_reason = isset( $data['block_reason'] ) && ! empty( $data['block_reason'] ) ? $data['block_reason'] : 'temporarily_blocked';
				return array(
					'blocked'       => true,
					'reason'        => $block_reason,
					'blocked_until' => $data['blocked_until'],
					'attempts'      => isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0,
				);
			}

			$cooldown_time = $settings['cooldown_time'];
			$attempts      = isset( $data['attempts'] ) ? $data['attempts'] : array();

			$previous_attempt = null;
			if ( count( $attempts ) >= 2 ) {
				$sorted_attempts = $attempts;
				rsort( $sorted_attempts );
				$most_recent_attempt    = $sorted_attempts[0];
				$second_to_last_attempt = $sorted_attempts[1];

				$time_between_attempts = $most_recent_attempt - $second_to_last_attempt;

				if ( $time_between_attempts < $cooldown_time ) {
					$previous_attempt = $second_to_last_attempt;
				}
			}

			if ( $previous_attempt && ( $now - $previous_attempt ) < $cooldown_time ) {
				$time_since_previous = $now - $previous_attempt;

				$calculated_blocked_until = $previous_attempt + $cooldown_time;

				if ( ! isset( $data['blocked_until'] ) || $data['blocked_until'] !== $calculated_blocked_until ) {
					if ( $calculated_blocked_until > $now ) {
						$data['blocked_until'] = $calculated_blocked_until;
						$data['block_reason']  = 'cooldown';
						$this->mosp_update_spam_data( $key, $data );
					}
				}

				$blocked_until = isset( $data['blocked_until'] ) && $data['blocked_until'] > $now ? $data['blocked_until'] : $calculated_blocked_until;
				$remaining     = $blocked_until - $now;

				return array(
					'blocked'       => true,
					'reason'        => 'cooldown',
					'blocked_until' => $blocked_until,
					'attempts'      => isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0,
					'remaining'     => $remaining,
				);
			} elseif ( $previous_attempt ) {
					$time_since_previous = $now - $previous_attempt;
			}

			$time_window      = MoSecurityHelper::COUNTING_WINDOW_SECONDS;
			$cutoff_time      = $now - $time_window;
			$data['attempts'] = array_filter(
				$data['attempts'],
				function ( $timestamp ) use ( $cutoff_time ) {
					return $timestamp > $cutoff_time;
				}
			);

			$max_attempts   = $settings['max_attempts'];
			$attempts_count = isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0;

			if ( $attempts_count > $max_attempts ) {
				$block_time_seconds    = $settings['block_time'];
				$data['blocked_until'] = $now + $block_time_seconds;
				$data['block_reason']  = 'max_attempts_exceeded';
				if ( ! isset( $data['total_blocks'] ) ) {
					$data['total_blocks'] = 0;
				}
				++$data['total_blocks'];
				$this->mosp_update_spam_data( $key, $data );

				return array(
					'blocked'       => true,
					'reason'        => 'max_attempts_exceeded',
					'blocked_until' => $data['blocked_until'],
					'attempts'      => $attempts_count,
				);
			}

			return array(
				'blocked'       => false,
				'reason'        => '',
				'blocked_until' => 0,
				'attempts'      => isset( $data['attempts'] ) && is_array( $data['attempts'] ) ? count( $data['attempts'] ) : 0,
			);
		}

		/**
		 * Check if identifier is whitelisted.
		 *
		 * @param string $identifier The identifier to check.
		 * @param string $type The type of identifier.
		 * @return bool
		 */
		public function mosp_is_whitelisted( $identifier, $type ) {
			$settings = $this->mosp_get_settings();

			switch ( $type ) {
				case 'ip':
					$identifier = trim( $identifier );

					if ( empty( $identifier ) || ! filter_var( $identifier, FILTER_VALIDATE_IP ) ) {
						return false;
					}

					$raw_whitelist = isset( $settings['whitelist_ips'] ) ? $settings['whitelist_ips'] : array();

					if ( is_string( $raw_whitelist ) ) {
						if ( ! empty( $raw_whitelist ) ) {
							$raw_whitelist = array_filter( array_map( 'trim', explode( "\n", $raw_whitelist ) ) );
							$raw_whitelist = array_values( $raw_whitelist );
						} else {
							$raw_whitelist = array();
						}
					}

					if ( ! empty( $raw_whitelist ) && is_array( $raw_whitelist ) ) {
						$whitelist_ips = array_map( 'trim', $raw_whitelist );
						$whitelist_ips = array_filter( $whitelist_ips );
						$whitelist_ips = array_values( $whitelist_ips );
					} else {
						$whitelist_ips = array();
					}

					foreach ( $whitelist_ips as $whitelist_ip ) {
						$whitelist_ip = trim( $whitelist_ip );
						if ( empty( $whitelist_ip ) ) {
							continue;
						}

						if ( $identifier === $whitelist_ip ) {
							return true;
						}

						if ( strpos( $whitelist_ip, '/' ) !== false ) {
							if ( $this->mosp_ip_in_range( $identifier, $whitelist_ip ) ) {
								return true;
							}
							continue;
						}

						$identifier_is_ipv6 = filter_var( $identifier, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
						$whitelist_is_ipv6  = filter_var( $whitelist_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );

						if ( $identifier_is_ipv6 && $whitelist_is_ipv6 ) {
							$normalized_identifier = $this->mosp_normalize_ipv6( $identifier );
							$normalized_whitelist  = $this->mosp_normalize_ipv6( $whitelist_ip );
							if ( $normalized_identifier === $normalized_whitelist ) {
								return true;
							}
						}

						$identifier_is_ipv4 = filter_var( $identifier, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
						$whitelist_is_ipv4  = filter_var( $whitelist_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );

						if ( $identifier_is_ipv4 && $whitelist_is_ipv4 ) {
							if ( $identifier === $whitelist_ip ) {
								return true;
							}
						}
					}
					return false;
				default:
					return false;
			}
		}

		/**
		 * Normalize IPv6 address to canonical form.
		 *
		 * @param string $ip IPv6 address.
		 * @return string Normalized IPv6 address or original IP if not IPv6.
		 */
		private function mosp_normalize_ipv6( $ip ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				return $ip;
			}

			if ( function_exists( 'inet_pton' ) && function_exists( 'inet_ntop' ) ) {
				$packed = inet_pton( $ip );
				if ( false !== $packed ) {
					$normalized = inet_ntop( $packed );
					if ( false !== $normalized ) {
						return strtolower( $normalized );
					}
				}
			}

			return strtolower( $ip );
		}

		/**
		 * Check if IP is in CIDR range (supports both IPv4 and IPv6).
		 *
		 * @param string $ip IP address to check.
		 * @param string $range CIDR range (e.g., "192.168.1.0/24" or "2001:db8::/32").
		 * @return bool True if IP is in range.
		 */
		private function mosp_ip_in_range( $ip, $range ) {
			if ( strpos( $range, '/' ) === false ) {
				return $ip === $range;
			}

			list( $subnet, $bits ) = explode( '/', $range );
			$bits                  = (int) $bits;

			if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) || ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) {
				return false;
			}

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				if ( $bits < 0 || $bits > 32 ) {
					return false;
				}
				$ip_long     = ip2long( $ip );
				$subnet_long = ip2long( $subnet );
				if ( false === $ip_long || false === $subnet_long ) {
					return false;
				}
				$mask         = -1 << ( 32 - $bits );
				$subnet_long &= $mask;
				return ( $ip_long & $mask ) === $subnet_long;
			}

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				if ( $bits < 0 || $bits > 128 ) {
					return false;
				}
				if ( function_exists( 'inet_pton' ) ) {
					$ip_packed     = inet_pton( $ip );
					$subnet_packed = inet_pton( $subnet );
					if ( false === $ip_packed || false === $subnet_packed ) {
						return false;
					}

					$ip_bytes     = unpack( 'C*', $ip_packed );
					$subnet_bytes = unpack( 'C*', $subnet_packed );

					$full_bytes   = intval( $bits / 8 );
					$partial_bits = $bits % 8;

					for ( $i = 1; $i <= $full_bytes; $i++ ) {
						if ( ! isset( $ip_bytes[ $i ] ) || ! isset( $subnet_bytes[ $i ] ) ) {
							return false;
						}
						if ( $ip_bytes[ $i ] !== $subnet_bytes[ $i ] ) {
							return false;
						}
					}

					if ( $partial_bits > 0 && $full_bytes < 16 ) {
						$byte_index = $full_bytes + 1;
						if ( ! isset( $ip_bytes[ $byte_index ] ) || ! isset( $subnet_bytes[ $byte_index ] ) ) {
							return false;
						}
						$mask = 0xFF << ( 8 - $partial_bits );
						if ( ( $ip_bytes[ $byte_index ] & $mask ) !== ( $subnet_bytes[ $byte_index ] & $mask ) ) {
							return false;
						}
					}

					return true;
				} else {
					$normalized_ip     = $this->mosp_normalize_ipv6( $ip );
					$normalized_subnet = $this->mosp_normalize_ipv6( $subnet );
					if ( 128 === $bits ) {
						return $normalized_ip === $normalized_subnet;
					}
					return false;
				}
			}

			return false;
		}

		/**
		 * Mark an identifier as requiring puzzle verification.
		 *
		 * @param string $identifier The identifier to mark.
		 * @return bool
		 */
		public function mosp_mark_puzzle_required( $identifier ) {
			$key          = 'mo_osp_puzzle_' . $this->mosp_hash_key( $identifier );
			$current_time = time();
			$expiry       = $current_time + ( 24 * 60 * 60 ); // 24 hours.

			$result = update_option( $key, $expiry );

			return $result;
		}

		/**
		 * Check if an identifier requires puzzle verification.
		 *
		 * @param string $identifier The identifier to check.
		 * @return bool
		 */
		public function mosp_is_puzzle_required( $identifier ) {
			$key          = 'mo_osp_puzzle_' . $this->mosp_hash_key( $identifier );
			$expiry       = get_option( $key );
			$current_time = time();

			if ( false === $expiry ) {
				$expiry = 0;
			}

			$required = ( $expiry && $expiry > $current_time );
			if ( $required ) {
				$remaining_time = $expiry - $current_time;
			}

			if ( $required ) {
				$remaining_time = $expiry - $current_time;
				return true;
			}

			if ( $expiry ) {
				delete_option( $key );
			}

			return false;
		}

		/**
		 * Clear puzzle requirement for an identifier.
		 *
		 * @param string $identifier The identifier to clear.
		 * @return bool
		 */
		public function mosp_clear_puzzle_requirement( $identifier ) {
			$key    = 'mo_osp_puzzle_' . $this->mosp_hash_key( $identifier );
			$result = delete_option( $key );
			return $result;
		}

		/**
		 * Check if user requires puzzle verification for any identifier.
		 *
		 * @param string $email      Email address.
		 * @param string $phone      Phone number.
		 * @param string $ip         IP address.
		 * @param string $browser_id Browser fingerprint.
		 * @return bool
		 */
		public function mosp_is_puzzle_required_for_user( $email, $phone, $ip, $browser_id ) {
			$identifiers = array(
				'email'   => $email,
				'phone'   => $phone,
				'ip'      => $ip,
				'browser' => $browser_id,
			);

			$prefixed_identifiers = array();
			if ( ! empty( $email ) ) {
				$prefixed_identifiers[] = 'email:' . $email;
			}
			if ( ! empty( $phone ) ) {
				$prefixed_identifiers[] = 'phone:' . $phone;
			}
			if ( ! empty( $ip ) ) {
				$prefixed_identifiers[] = 'ip:' . $ip;
			}
			if ( ! empty( $browser_id ) ) {
				$prefixed_identifiers[] = 'browser:' . $browser_id;
			}

			if ( empty( $email ) && empty( $phone ) && empty( $ip ) && empty( $browser_id ) ) {
				return false;
			}

			foreach ( $identifiers as $type => $identifier ) {
				if ( ! empty( $identifier ) ) {
					$required = $this->mosp_is_puzzle_required( $identifier );
					if ( $required ) {
						return true;
					}
				}
			}

			foreach ( $prefixed_identifiers as $prefixed_id ) {
				$required = $this->mosp_is_puzzle_required( $prefixed_id );
				if ( $required ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Cleanup expired entries.
		 */
		public function mosp_cleanup_expired_entries() {
			global $wpdb;

			$settings = $this->mosp_get_settings();
			$now      = time();
			$cutoff   = $now - ( MoSecurityHelper::COUNTING_WINDOW_SECONDS * 2 ); // Keep data for 2x counting window (30 minutes).

			$deleted = $this->cleanup_spam_data( $cutoff );

			$deleted += $this->cleanup_rate_limiting_data( $now );

			$deleted += $this->cleanup_permanent_puzzle_flags( $now - ( 30 * 24 * 60 * 60 ) );

			$this->mosp_prune_if_needed();
		}

		/**
		 * Cleanup main spam data entries.
		 *
		 * @param int $cutoff Cutoff timestamp.
		 * @return int Number of deleted entries.
		 */
		private function cleanup_spam_data( $cutoff ) {
			global $wpdb;

			$cache_key    = 'mosp_spam_data_option_names';
			$option_names = wp_cache_get( $cache_key, 'mo_osp' );
			if ( false === $option_names ) {
				$option_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_' . self::SPAM_DATA_PREFIX ) . '%'
					)
				);
				wp_cache_set( $cache_key, $option_names, 'mo_osp' );
			}

			$deleted = 0;
			foreach ( $option_names as $db_option_name ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

				$data = get_mo_option( $option_key );

				if ( is_array( $data ) ) {
					if ( isset( $data['last_attempt'] ) && $data['last_attempt'] < $cutoff &&
						( ! isset( $data['blocked_until'] ) || $data['blocked_until'] < time() ) ) {
						delete_mo_option( $option_key );
						wp_cache_delete( $db_option_name, 'mo_osp' );
						++$deleted;
					}
				}
			}

			if ( $deleted > 0 ) {
				wp_cache_delete( $cache_key, 'mo_osp' );
			}

			return $deleted;
		}

		/**
		 * Cleanup rate limiting data (hourly/daily).
		 *
		 * @param int $now Current timestamp.
		 * @return int Number of deleted entries
		 */
		private function cleanup_rate_limiting_data( $now ) {
			global $wpdb;

			$deleted = 0;

			$hourly_cutoff  = $now - ( 2 * 60 * 60 );
			$hourly_cache   = 'mosp_rate_limit_hourly_option_names';
			$hourly_options = wp_cache_get( $hourly_cache, 'mo_osp' );
			if ( false === $hourly_options ) {
				$hourly_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_hourly_' ) . '%'
					)
				);
				wp_cache_set( $hourly_cache, $hourly_options, 'mo_osp' );
			}

			foreach ( $hourly_options as $db_option_name ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

				$data = get_mo_option( $option_key );
				if ( is_array( $data ) && isset( $data['last_attempt'] ) && $data['last_attempt'] < $hourly_cutoff ) {
					delete_mo_option( $option_key );
					++$deleted;
				}
			}

			$daily_cutoff  = $now - ( 2 * 24 * 60 * 60 );
			$daily_cache   = 'mosp_rate_limit_daily_option_names';
			$daily_options = wp_cache_get( $daily_cache, 'mo_osp' );
			if ( false === $daily_options ) {
				$daily_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_daily_' ) . '%'
					)
				);
				wp_cache_set( $daily_cache, $daily_options, 'mo_osp' );
			}

			foreach ( $daily_options as $db_option_name ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

				$data = get_mo_option( $option_key );
				if ( is_array( $data ) && isset( $data['last_attempt'] ) && $data['last_attempt'] < $daily_cutoff ) {
					delete_mo_option( $option_key );
					++$deleted;
				}
			}

			if ( $deleted > 0 ) {
				wp_cache_delete( $hourly_cache, 'mo_osp' );
				wp_cache_delete( $daily_cache, 'mo_osp' );
			}

			return $deleted;
		}

		/**
		 * Cleanup permanent puzzle completion flags.
		 *
		 * @param int $cutoff Cutoff timestamp (30 days ago).
		 * @return int Number of deleted entries
		 */
		private function cleanup_permanent_puzzle_flags( $cutoff ) {
			global $wpdb;

			$deleted        = 0;
			$cache_key      = 'mosp_puzzle_completion_option_names';
			$puzzle_options = wp_cache_get( $cache_key, 'mo_osp' );
			if ( false === $puzzle_options ) {
				$puzzle_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_puzzle_ever_completed_' ) . '%'
					)
				);
				wp_cache_set( $cache_key, $puzzle_options, 'mo_osp' );
			}

			foreach ( $puzzle_options as $db_option_name ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

				$completion_time = get_mo_option( $option_key );
				if ( is_numeric( $completion_time ) && $completion_time < $cutoff ) {
					delete_mo_option( $option_key );
					++$deleted;
				}
			}

			if ( $deleted > 0 ) {
				wp_cache_delete( $cache_key, 'mo_osp' );
			}

			return $deleted;
		}

		/**
		 * Prune entries if still too many.
		 */
		private function mosp_prune_if_needed() {
			global $wpdb;

			$cache_key     = 'mosp_spam_storage_total_count';
			$total_options = wp_cache_get( $cache_key, 'mo_osp' );
			if ( false === $total_options ) {
				$total_options = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_' . self::SPAM_DATA_PREFIX ) . '%',
						$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_' ) . '%',
						$wpdb->esc_like( 'mo_customer_validation_puzzle_ever_completed_' ) . '%'
					)
				);
				wp_cache_set( $cache_key, $total_options, 'mo_osp' );
			}

			if ( $total_options > self::MAX_ENTRIES ) {
				$this->mosp_prune_old_entries( self::MAX_ENTRIES );
				wp_cache_delete( $cache_key, 'mo_osp' );
			}
		}

		/**
		 * Prune old entries to keep storage bounded.
		 *
		 * @param int $max_entries Maximum entries to keep.
		 * @return void
		 */
		private function mosp_prune_old_entries( $max_entries ) {
			global $wpdb;

			$cache_key = 'mosp_spam_data_entries';
			$results   = wp_cache_get( $cache_key, 'mo_osp' );
			if ( false === $results ) {
				$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC",
						$wpdb->esc_like( 'mo_customer_validation_' . self::SPAM_DATA_PREFIX ) . '%'
					)
				);
				wp_cache_set( $cache_key, $results, 'mo_osp' );
			}

			if ( count( $results ) <= $max_entries ) {
				return;
			}

			$entries = array();
			foreach ( $results as $result ) {
				$data = maybe_unserialize( $result->option_value );
				if ( is_array( $data ) && isset( $data['last_attempt'] ) ) {
					$entries[] = array(
						'option_name'  => $result->option_name,
						'last_attempt' => $data['last_attempt'],
					);
				}
			}

			usort(
				$entries,
				function ( $a, $b ) {
					return $b['last_attempt'] - $a['last_attempt'];
				}
			);

			$to_delete = array_slice( $entries, $max_entries );
			foreach ( $to_delete as $entry ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $entry['option_name'] );

				delete_mo_option( $option_key );
				wp_cache_delete( $entry['option_name'], 'mo_osp' );
			}

			wp_cache_delete( $cache_key, 'mo_osp' );
		}

		/**
		 * Get masked version of identifier for logging.
		 *
		 * @param string $identifier The identifier to mask.
		 * @param string $type The type of identifier.
		 * @return string
		 */
		public function mosp_mask_identifier( $identifier, $type ) {
			switch ( $type ) {
				case 'phone':
					if ( strlen( $identifier ) > 4 ) {
						return str_repeat( 'X', strlen( $identifier ) - 4 ) . substr( $identifier, -4 );
					}
					return $identifier;

				case 'email':
					$parts = explode( '@', $identifier );
					if ( count( $parts ) === 2 ) {
						$username        = $parts[0];
						$domain          = $parts[1];
						$masked_username = strlen( $username ) > 2 ? substr( $username, 0, 1 ) . str_repeat( '*', strlen( $username ) - 2 ) . substr( $username, -1 ) : $username;
						return $masked_username . '@' . $domain;
					}
					return $identifier;

				case 'ip':
					$parts = explode( '.', $identifier );
					if ( count( $parts ) === 4 ) {
						return $parts[0] . '.' . $parts[1] . '.XXX.XXX';
					}
					return $identifier;

				default:
					return substr( $identifier, 0, 8 ) . '...';
			}
		}

		/**
		 * Record attempt with timestamp (new method for integration).
		 *
		 * @param string $identifier The full identifier (e.g., 'email:user@example.com').
		 * @param int    $timestamp The attempt timestamp.
		 * @param array  $context Optional context data.
		 * @return void
		 */
		public function mosp_record_attempt_with_timestamp( $identifier, $timestamp, $context = array() ) {
			$key  = $this->mosp_hash_key( $identifier );
			$data = $this->mosp_get_spam_data( $key );

			if ( false === $data ) {
				$data = array(
					'attempts'      => array(),
					'blocked_until' => 0,
					'created'       => $timestamp,
				);
			}

			if ( is_array( $context ) ) {
				if ( ! empty( $context['ip'] ) && filter_var( $context['ip'], FILTER_VALIDATE_IP ) ) {
					$data['last_ip'] = $context['ip'];
				}
				if ( ! empty( $context['browser_id'] ) ) {
					$data['last_browser'] = $context['browser_id'];
				}
				if ( ! empty( $context['email'] ) ) {
					$data['last_email'] = strtolower( trim( (string) $context['email'] ) );
				}
				if ( ! empty( $context['phone'] ) ) {
					$data['last_phone'] = trim( (string) $context['phone'] );
				}
			}

			if ( is_string( $identifier ) && strpos( $identifier, ':' ) !== false ) {
				list( $id_type, $id_value ) = explode( ':', $identifier, 2 );
				$id_value                   = trim( (string) $id_value );
				if ( ! empty( $id_value ) ) {
					if ( 'email' === $id_type ) {
						$data['last_email'] = strtolower( $id_value );
					} elseif ( 'phone' === $id_type ) {
						$data['last_phone'] = $id_value;
					} elseif ( 'ip' === $id_type ) {
						$data['last_ip'] = $id_value;
					} elseif ( 'browser' === $id_type ) {
						$data['last_browser'] = $id_value;
					}
				}
			}

			if ( ! isset( $data['attempts'] ) ) {
				$data['attempts'] = array();
			}

			$data['attempts'][]   = $timestamp;
			$data['last_attempt'] = $timestamp;

			$cutoff           = $timestamp - ( 24 * 60 * 60 );
			$data['attempts'] = array_filter(
				$data['attempts'],
				function ( $time ) use ( $cutoff ) {
					return $time > $cutoff;
				}
			);

			$this->mosp_update_spam_data( $key, $data );
		}

		/**
		 * Get all currently blocked users.
		 *
		 * @param int $limit Maximum number of entries to return (default 100).
		 * @param int $offset Offset for pagination (default 0).
		 * @return array Array of blocked user data.
		 */
		public function mosp_get_all_blocked_users( $limit = 100, $offset = 0 ) {
			return $this->mosp_get_blocked_users_from_rate_limits( $limit, $offset );
		}

		/**
		 * Delete all spam/block rows, rate-limit options, and puzzle-requirement flags (admin "clear all").
		 *
		 * @return int Number of options deleted.
		 */
		public function mosp_clear_all_otp_spam_data() {
			global $wpdb;

			$deleted = 0;

			$like_patterns = array(
				$wpdb->esc_like( 'mo_customer_validation_' . self::SPAM_DATA_PREFIX ) . '%',
				$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_' ) . '%',
			);

			foreach ( $like_patterns as $like ) {
				$option_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$like
					)
				);
				foreach ( $option_names as $option_name ) {
					delete_site_option( $option_name );
					++$deleted;
				}
			}

			$puzzle_like  = $wpdb->esc_like( 'mo_osp_puzzle_' ) . '%';
			$puzzle_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
					$puzzle_like
				)
			);
			foreach ( $puzzle_names as $option_name ) {
				delete_option( $option_name );
				++$deleted;
			}

			wp_cache_delete( 'mosp_blocked_users_list', 'mo_osp' );
			wp_cache_delete( 'mosp_spam_data_option_names', 'mo_osp' );
			wp_cache_delete( 'mosp_uninstall_spam_option_names', 'mo_osp' );
			wp_cache_delete( 'mosp_rate_limit_hourly_options', 'mo_osp' );
			wp_cache_delete( 'mosp_rate_limit_daily_options', 'mo_osp' );
			wp_cache_delete( 'mosp_rate_limit_hourly_option_names', 'mo_osp' );
			wp_cache_delete( 'mosp_rate_limit_daily_option_names', 'mo_osp' );

			return $deleted;
		}

		/**
		 * Get blocked users by checking rate limit data and spam data.
		 *
		 * @param int $limit Maximum number of entries to return.
		 * @param int $offset Offset for pagination.
		 * @return array Array of blocked user data.
		 */
		public function mosp_get_blocked_users_from_rate_limits( $limit = 100, $offset = 0 ) {
			global $wpdb;

			$now                = time();
			$blocked            = array();
			$settings           = $this->mosp_get_settings();
			$window_types       = array( 'hourly', 'daily' );
			$seen_hashes        = array();
			$hash_to_identifier = array();
			$priority           = array(
				'phone'   => 3,
				'email'   => 2,
				'ip'      => 1,
				'browser' => 0,
			);

			foreach ( $window_types as $window_type ) {
				$cache_key    = 'mosp_rate_limit_' . $window_type . '_options';
				$option_names = wp_cache_get( $cache_key, 'mo_osp' );

				if ( false === $option_names ) {
					$option_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
							$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_' . $window_type . '_' ) . '%'
						)
					);
					wp_cache_set( $cache_key, $option_names, 'mo_osp', 300 );
				}

				foreach ( $option_names as $db_option_name ) {
					$option_key     = str_replace( 'mo_customer_validation_', '', $db_option_name );
					$rate_limit_key = str_replace( self::SPAM_DATA_PREFIX, '', $option_key );

					$key_parts = explode( '_', $rate_limit_key );
					if ( count( $key_parts ) >= 4 ) {
						$identifier_hash = $key_parts[3];

						if ( ! isset( $hash_to_identifier[ $identifier_hash ] ) ) {
							$hash_to_identifier[ $identifier_hash ] = null;
						}
					}
				}
			}

			$cache_key         = 'mosp_spam_data_option_names';
			$spam_option_names = wp_cache_get( $cache_key, 'mo_osp' );

			if ( false === $spam_option_names ) {
				$spam_option_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( 'mo_customer_validation_' . self::SPAM_DATA_PREFIX ) . '%'
					)
				);
				wp_cache_set( $cache_key, $spam_option_names, 'mo_osp', 300 );
			}

			foreach ( $spam_option_names as $db_option_name ) {
				$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

				$hash_key = str_replace( self::SPAM_DATA_PREFIX, '', $option_key );

				if ( strpos( $option_key, 'rate_limit_' ) !== false ) {
					continue;
				}

				$spam_data = $this->mosp_get_spam_data( $hash_key );

				if ( false === $spam_data || ! is_array( $spam_data ) ) {
					continue;
				}

				$blocked_until = isset( $spam_data['blocked_until'] ) ? (int) $spam_data['blocked_until'] : 0;
				$block_reason  = isset( $spam_data['block_reason'] ) ? $spam_data['block_reason'] : '';

				if ( $blocked_until > $now && in_array( $block_reason, array( 'hourly_limit_exceeded', 'daily_limit_exceeded', 'max_attempts_exceeded' ), true ) ) {
					$remaining_time = $blocked_until - $now;

					$identifier_type  = isset( $spam_data['type'] ) ? $spam_data['type'] : 'unknown';
					$identifier_value = isset( $spam_data['identifier'] ) ? $spam_data['identifier'] : '';

					if ( ! empty( $identifier_value ) ) {
						$identifier_display = $identifier_value;
					} else {
						$identifier_display = 'User: ' . substr( $hash_key, -8 );
					}

					if ( 'unknown' === $identifier_type || 'identifier' === $identifier_type ) {
						$identifier_info = $this->mosp_infer_identifier_from_hash( $hash_key, $spam_data );
						$identifier_type = $identifier_info['type'];
						if ( empty( $identifier_value ) && ! empty( $identifier_info['value'] ) ) {
							$identifier_value   = $identifier_info['value'];
							$identifier_display = $identifier_value;
						}
					}

					$user_key = $block_reason . '_' . $blocked_until;

					if ( in_array( $hash_key, $seen_hashes, true ) ) {
						continue;
					}

					$is_duplicate = false;
					foreach ( $blocked as $existing ) {
						if ( $existing['block_reason'] === $block_reason &&
							abs( $existing['blocked_until'] - $blocked_until ) < 5 && // Within 5 seconds.
							'unknown' !== $existing['identifier_type'] &&
							'unknown' !== $identifier_type ) {
							$existing_priority = isset( $priority[ $existing['identifier_type'] ] ) ? $priority[ $existing['identifier_type'] ] : 0;
							$current_priority  = isset( $priority[ $identifier_type ] ) ? $priority[ $identifier_type ] : 0;

							if ( $current_priority > $existing_priority ) {
								$blocked      = array_filter(
									$blocked,
									function ( $item ) use ( $existing ) {
										return $item['identifier_hash'] !== $existing['identifier_hash'];
									}
								);
								$blocked      = array_values( $blocked );
								$is_duplicate = false;
								break;
							} else {
								$is_duplicate = true;
								break;
							}
						}
					}

					if ( $is_duplicate ) {
						continue;
					}

					$blocked[] = array(
						'identifier_hash'   => $hash_key,
						'identifier_masked' => $identifier_display,
						'identifier_type'   => $identifier_type,
						'identifier_value'  => $identifier_value,
						'block_reason'      => $block_reason,
						'blocked_until'     => $blocked_until,
						'remaining_time'    => $remaining_time,
					);

					$seen_hashes[] = $hash_key;
				}
			}

			foreach ( $window_types as $window_type ) {
				$cache_key    = 'mosp_rate_limit_' . $window_type . '_options';
				$option_names = wp_cache_get( $cache_key, 'mo_osp' );

				if ( false === $option_names ) {
					$option_names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
							$wpdb->esc_like( 'mo_customer_validation_mo_osp_rate_limit_' . $window_type . '_' ) . '%'
						)
					);
					wp_cache_set( $cache_key, $option_names, 'mo_osp', 300 );
				}

				$limit_value = 'hourly' === $window_type ? $settings['hourly_limit'] : $settings['daily_limit'];

				foreach ( $option_names as $db_option_name ) {
					$option_key = str_replace( 'mo_customer_validation_', '', $db_option_name );

					$rate_limit_key = str_replace( self::SPAM_DATA_PREFIX, '', $option_key );
					$rate_data      = $this->mosp_get_spam_data( $rate_limit_key );

					if ( false === $rate_data || ! is_array( $rate_data ) || ! isset( $rate_data['attempts'] ) || ! is_array( $rate_data['attempts'] ) ) {
						continue;
					}

					$window_seconds   = 'hourly' === $window_type ? 3600 : 86400;
					$window_start     = $now - $window_seconds;
					$current_attempts = count(
						array_filter(
							$rate_data['attempts'],
							function ( $timestamp ) use ( $window_start ) {
								return $timestamp > $window_start;
							}
						)
					);

					if ( $current_attempts >= $limit_value ) {
						$key_parts = explode( '_', $rate_limit_key );
						if ( count( $key_parts ) >= 4 ) {
							$identifier_hash = $key_parts[3];

							if ( in_array( $identifier_hash, $seen_hashes, true ) ) {
								continue;
							}

							$in_window = array_filter(
								$rate_data['attempts'],
								function ( $timestamp ) use ( $window_start ) {
									return $timestamp > $window_start;
								}
							);

							if ( ! empty( $in_window ) ) {
								$oldest_attempt = min( $in_window );
								$reset_time     = $oldest_attempt + $window_seconds;
								$remaining_time = max( 0, $reset_time - $now );

								$spam_data = $this->mosp_get_spam_data( $identifier_hash );

								$blocked_until = 0;
								$block_reason  = $window_type . '_limit_exceeded';

								if ( false !== $spam_data && is_array( $spam_data ) && isset( $spam_data['blocked_until'] ) && $spam_data['blocked_until'] > $now ) {
									$blocked_until  = $spam_data['blocked_until'];
									$block_reason   = isset( $spam_data['block_reason'] ) ? $spam_data['block_reason'] : $block_reason;
									$remaining_time = $blocked_until - $now;
								}

								$identifier_type    = 'unknown';
								$identifier_value   = '';
								$identifier_display = 'User: ' . substr( $identifier_hash, -8 );

								if ( false !== $spam_data && is_array( $spam_data ) ) {
									if ( isset( $spam_data['type'] ) ) {
										$identifier_type = $spam_data['type'];
									}
									if ( isset( $spam_data['identifier'] ) && ! empty( $spam_data['identifier'] ) ) {
										$identifier_value   = $spam_data['identifier'];
										$identifier_display = $identifier_value;
									}
								}

								if ( empty( $identifier_value ) && isset( $rate_data['identifier'] ) ) {
									$rate_identifier = $rate_data['identifier'];
									if ( strpos( $rate_identifier, 'phone:' ) === 0 ) {
										$identifier_type    = 'phone';
										$identifier_value   = substr( $rate_identifier, 6 );
										$identifier_display = $identifier_value;
									} elseif ( strpos( $rate_identifier, 'email:' ) === 0 ) {
										$identifier_type    = 'email';
										$identifier_value   = substr( $rate_identifier, 6 );
										$identifier_display = $identifier_value;
									}
								}

								if ( 'unknown' === $identifier_type || 'identifier' === $identifier_type ) {
									if ( ! empty( $spam_data['last_email'] ) ) {
										$identifier_type    = 'email';
										$identifier_value   = $spam_data['last_email'];
										$identifier_display = $identifier_value;
									} elseif ( ! empty( $spam_data['last_phone'] ) ) {
										$identifier_type    = 'phone';
										$identifier_value   = $spam_data['last_phone'];
										$identifier_display = $identifier_value;
									} elseif ( ! empty( $spam_data['last_ip'] ) ) {
										$identifier_type    = 'ip';
										$identifier_value   = $spam_data['last_ip'];
										$identifier_display = $identifier_value;
									} elseif ( ! empty( $spam_data['last_browser'] ) ) {
										$identifier_type    = 'browser';
										$identifier_value   = $spam_data['last_browser'];
										$identifier_display = $identifier_value;
									} elseif ( ! empty( $identifier_value ) && strpos( $identifier_value, '@' ) !== false ) {
										$identifier_type = 'email';
									}
								}

								$calculated_blocked_until = $blocked_until > 0 ? $blocked_until : ( $now + $remaining_time );
								$is_duplicate             = false;
								foreach ( $blocked as $existing ) {
									if ( $existing['block_reason'] === $block_reason &&
										abs( $existing['blocked_until'] - $calculated_blocked_until ) < 5 ) {
										$existing_priority = isset( $priority[ $existing['identifier_type'] ] ) ? $priority[ $existing['identifier_type'] ] : 0;
										$current_priority  = isset( $priority[ $identifier_type ] ) ? $priority[ $identifier_type ] : 0;

										if ( $current_priority > $existing_priority ) {
											$blocked      = array_filter(
												$blocked,
												function ( $item ) use ( $existing ) {
													return $item['identifier_hash'] !== $existing['identifier_hash'];
												}
											);
											$blocked      = array_values( $blocked );
											$is_duplicate = false;
											break;
										} else {
											$is_duplicate = true;
											break;
										}
									}
								}

								if ( $is_duplicate ) {
									continue;
								}

								$blocked[] = array(
									'identifier_hash'   => $identifier_hash,
									'identifier_masked' => $identifier_display,
									'identifier_type'   => $identifier_type,
									'identifier_value'  => $identifier_value,
									'block_reason'      => $block_reason,
									'blocked_until'     => $calculated_blocked_until,
									'remaining_time'    => $remaining_time,
								);

								$seen_hashes[] = $identifier_hash;
							}
						}
					}
				}
			}

			// Sort by remaining time (longest first).
			usort(
				$blocked,
				function ( $a, $b ) {
					return $b['remaining_time'] - $a['remaining_time'];
				}
			);

			$total   = count( $blocked );
			$blocked = array_slice( $blocked, $offset, $limit );

			return array(
				'users' => $blocked,
				'total' => $total,
			);
		}

		/**
		 * Infer identifier type and value from hash by checking rate limit data.
		 *
		 * @param string $hash The identifier hash.
		 * @param array  $spam_data The spam data array.
		 * @return array Array with 'type', 'value', and 'masked' keys (masked now contains original value).
		 */
		private function mosp_infer_identifier_from_hash( $hash, $spam_data ) {
			global $wpdb;

			$result = array(
				'type'   => 'unknown',
				'value'  => '',
				'masked' => 'User: ' . substr( $hash, -8 ),
			);

			if ( isset( $spam_data['identifier'] ) && ! empty( $spam_data['identifier'] ) ) {
				$result['value']  = $spam_data['identifier'];
				$result['masked'] = $spam_data['identifier'];
			}

			if ( isset( $spam_data['type'] ) && 'identifier' !== $spam_data['type'] && 'unknown' !== $spam_data['type'] ) {
				$result['type'] = $spam_data['type'];
			}

			$window_types = array( 'hourly', 'daily' );
			foreach ( $window_types as $window_type ) {
				$rate_limit_key = 'rate_limit_' . $window_type . '_' . $hash;
				$rate_data      = $this->mosp_get_spam_data( $rate_limit_key );

				if ( false !== $rate_data && is_array( $rate_data ) ) {
					if ( isset( $rate_data['identifier'] ) && ! empty( $rate_data['identifier'] ) ) {
						$rate_identifier = $rate_data['identifier'];
						if ( strpos( $rate_identifier, 'phone:' ) === 0 ) {
							$result['type']   = 'phone';
							$result['value']  = substr( $rate_identifier, 6 );
							$result['masked'] = $result['value'];
						} elseif ( strpos( $rate_identifier, 'email:' ) === 0 ) {
							$result['type']   = 'email';
							$result['value']  = substr( $rate_identifier, 6 );
							$result['masked'] = $result['value'];
						}
					} elseif ( 'unknown' === $result['type'] ) {
						if ( ! empty( $spam_data['last_email'] ) ) {
							$result['type']   = 'email';
							$result['value']  = $spam_data['last_email'];
							$result['masked'] = $result['value'];
						} elseif ( ! empty( $spam_data['last_phone'] ) ) {
							$result['type']   = 'phone';
							$result['value']  = $spam_data['last_phone'];
							$result['masked'] = $result['value'];
						} elseif ( ! empty( $spam_data['last_ip'] ) ) {
							$result['type']   = 'ip';
							$result['value']  = $spam_data['last_ip'];
							$result['masked'] = $result['value'];
						} elseif ( ! empty( $spam_data['last_browser'] ) ) {
							$result['type']   = 'browser';
							$result['value']  = $spam_data['last_browser'];
							$result['masked'] = $result['value'];
						}
					}
					break;
				}
			}

			if ( 'unknown' === $result['type'] ) {
				if ( ! empty( $spam_data['last_email'] ) ) {
					$result['type']   = 'email';
					$result['value']  = $spam_data['last_email'];
					$result['masked'] = $result['value'];
				} elseif ( ! empty( $spam_data['last_phone'] ) ) {
					$result['type']   = 'phone';
					$result['value']  = $spam_data['last_phone'];
					$result['masked'] = $result['value'];
				} elseif ( ! empty( $spam_data['last_ip'] ) ) {
					$result['type']   = 'ip';
					$result['value']  = $spam_data['last_ip'];
					$result['masked'] = $result['value'];
				} elseif ( ! empty( $spam_data['last_browser'] ) ) {
					$result['type']   = 'browser';
					$result['value']  = $spam_data['last_browser'];
					$result['masked'] = $result['value'];
				}
			}

			return $result;
		}
	}
}
