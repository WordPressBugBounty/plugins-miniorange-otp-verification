<?php
/**
 * Handles writing, reading, and clearing the plugin's debug log file.
 *
 * @package miniorange-otp-verification/helper
 */

namespace OTP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoLogger' ) ) {
	/**
	 * Self-contained file-based debug logger. Writes are gated by the
	 * 'enable_debug_logs' plugin option and land in a protected, per-site
	 * unguessable directory under wp-content/uploads, never inside the
	 * plugin folder itself.
	 */
	class MoLogger {

		/** Directory name prefix under wp_upload_dir() that holds the log file. */
		const LOG_DIR_PREFIX = 'mo-otp-logs';

		/** Log file name. */
		const LOG_FILE_NAME = 'mo-otp-debug.log';

		/** Option name storing the random per-site directory suffix. */
		const DIR_TOKEN_OPTION = 'mo_log_dir_token';

		/**
		 * Returns true if debug logging is currently enabled.
		 *
		 * @return bool
		 */
		public static function is_logging_enabled() {
			return (bool) get_mo_option( 'enable_debug_logs' );
		}

		/**
		 * Returns the existing random per-site directory suffix, generating
		 * and persisting one on first use. This keeps the log path from
		 * being identical (and therefore guessable/scannable) across every
		 * site running this plugin.
		 *
		 * @return string
		 */
		private static function get_or_create_dir_token() {
			$token = get_mo_option( self::DIR_TOKEN_OPTION );
			if ( empty( $token ) || ! is_string( $token ) ) {
				$token = substr( wp_generate_password( 20, false, false ), 0, 12 );
				update_mo_option( self::DIR_TOKEN_OPTION, $token );
			}
			return $token;
		}

		/**
		 * Absolute path to the protected log directory.
		 *
		 * @return string
		 */
		private static function get_log_dir() {
			$upload_dir = wp_upload_dir();
			return trailingslashit( $upload_dir['basedir'] ) . self::LOG_DIR_PREFIX . '-' . self::get_or_create_dir_token();
		}

		/**
		 * Absolute path to the log file.
		 *
		 * @return string
		 */
		private static function get_log_file_path() {
			return self::get_log_dir() . '/' . self::LOG_FILE_NAME;
		}

		/**
		 * Initializes the WordPress Filesystem API and returns it.
		 *
		 * @global \WP_Filesystem_Base $wp_filesystem
		 * @return \WP_Filesystem_Base|null
		 */
		private static function get_filesystem() {
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
			return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
		}

		/**
		 * Creates the log directory (if missing) and drops a deny-all
		 * .htaccess plus a blank index.php. This is defense-in-depth for
		 * Apache; the directory name's random per-site suffix is what
		 * actually prevents the log from being found/scanned on servers
		 * (e.g. Nginx) that don't honor .htaccess at all.
		 *
		 * @param \WP_Filesystem_Base $filesystem Initialized filesystem instance.
		 * @return void
		 */
		private static function ensure_log_dir_protected( $filesystem ) {
			$dir = self::get_log_dir();
			wp_mkdir_p( $dir );

			$htaccess = $dir . '/.htaccess';
			if ( ! $filesystem->exists( $htaccess ) ) {
				$filesystem->put_contents( $htaccess, "Deny from all\n", FS_CHMOD_FILE );
			}

			$index = $dir . '/index.php';
			if ( ! $filesystem->exists( $index ) ) {
				$filesystem->put_contents( $index, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
			}
		}

		/**
		 * Converts a value to a loggable string.
		 *
		 * @param mixed $value Value to stringify.
		 * @return string
		 */
		private static function stringify( $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				return (string) wp_json_encode( $value );
			}
			return (string) $value;
		}

		/**
		 * Atomically appends $entry to the log file using a direct,
		 * exclusively-locked file handle. WP_Filesystem_Base has no atomic
		 * append primitive, and a read-then-rewrite-whole-file approach
		 * would lose entries when two requests log at the same time.
		 *
		 * @param string $entry Text to append.
		 * @return void
		 */
		private static function append_to_log_file( $entry ) {
			$log_path = self::get_log_file_path();
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_read_fopen
			$handle = fopen( $log_path, 'ab' );
			if ( false === $handle ) {
				return;
			}
			if ( flock( $handle, LOCK_EX ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				fwrite( $handle, $entry );
				flock( $handle, LOCK_UN );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
		}

		/**
		 * Appends one log entry, if logging is enabled. No-ops entirely
		 * when disabled, so this is safe to call unconditionally from
		 * any outbound-request code path.
		 *
		 * @param string $context       Short label for the log entry, e.g. 'API Call' or 'API Error'.
		 * @param string $url           Request URL.
		 * @param mixed  $request_body  Request payload.
		 * @param mixed  $response_body Response payload.
		 * @return void
		 */
		public static function log( $context, $url = '', $request_body = '', $response_body = '' ) {
			if ( ! self::is_logging_enabled() ) {
				return;
			}

			$filesystem = self::get_filesystem();
			if ( ! $filesystem ) {
				return;
			}

			self::ensure_log_dir_protected( $filesystem );

			$entry  = '[' . current_time( 'mysql' ) . '] [' . $context . ']' . "\n";
			$entry .= '  URL     : ' . self::stringify( $url ) . "\n";
			$entry .= '  REQUEST : ' . self::stringify( $request_body ) . "\n";
			$entry .= '  RESPONSE: ' . self::stringify( $response_body ) . "\n";
			$entry .= str_repeat( '-', 80 ) . "\n";

			self::append_to_log_file( $entry );
		}

		/**
		 * Returns true if a non-empty log file currently exists.
		 *
		 * @return bool
		 */
		public static function log_file_exists() {
			$filesystem = self::get_filesystem();
			if ( ! $filesystem ) {
				return false;
			}
			$log_path = self::get_log_file_path();
			return $filesystem->exists( $log_path ) && $filesystem->size( $log_path ) > 0;
		}

		/**
		 * Returns a human-readable log file size, or an empty string if none exists.
		 *
		 * @return string
		 */
		public static function get_log_size() {
			if ( ! self::log_file_exists() ) {
				return '';
			}
			$filesystem = self::get_filesystem();
			if ( ! $filesystem ) {
				return '';
			}
			$bytes = $filesystem->size( self::get_log_file_path() );
			if ( $bytes >= MB_IN_BYTES ) {
				return round( $bytes / MB_IN_BYTES, 2 ) . ' MB';
			}
			if ( $bytes >= KB_IN_BYTES ) {
				return round( $bytes / KB_IN_BYTES, 2 ) . ' KB';
			}
			return $bytes . ' B';
		}

		/**
		 * Returns the log file's last-modified timestamp, or an empty string if none exists.
		 *
		 * @return string
		 */
		public static function get_log_last_modified() {
			if ( ! self::log_file_exists() ) {
				return '';
			}
			$filesystem = self::get_filesystem();
			if ( ! $filesystem ) {
				return '';
			}
			$timestamp = $filesystem->mtime( self::get_log_file_path() );
			return $timestamp ? gmdate( 'Y-m-d H:i:s', (int) $timestamp ) : '';
		}

		/**
		 * Deletes the log file, if present.
		 *
		 * @return void
		 */
		public static function clear_logs() {
			$filesystem = self::get_filesystem();
			if ( ! $filesystem ) {
				return;
			}
			$log_path = self::get_log_file_path();
			if ( $filesystem->exists( $log_path ) ) {
				$filesystem->delete( $log_path );
			}
		}

		/**
		 * Streams the log file to the browser as a download and exits.
		 *
		 * @return void
		 */
		public static function download_log() {
			if ( ! self::log_file_exists() ) {
				wp_die( esc_html__( 'No debug log file found.', 'miniorange-otp-verification' ) );
			}

			$log_path = self::get_log_file_path();
			$filename = 'mo-otp-debug-log-' . gmdate( 'Y-m-d' ) . '.log';

			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

			$filesystem = self::get_filesystem();
			if ( $filesystem && ! ini_get( 'zlib.output_compression' ) ) {
				header( 'Content-Length: ' . $filesystem->size( $log_path ) );
			}
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			if ( function_exists( 'readfile' ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
				readfile( $log_path );
			} else {
				self::stream_in_chunks( $log_path );
			}
			exit;
		}

		/**
		 * Fallback used only when readfile() has been disabled on the host:
		 * reads and outputs the file in fixed-size chunks via fopen()/fread(),
		 * so memory usage still stays constant regardless of file size.
		 *
		 * @param string $path Absolute file path.
		 * @return void
		 */
		private static function stream_in_chunks( $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen( $path, 'rb' );
			if ( false === $handle ) {
				return;
			}
			while ( ! feof( $handle ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped
				echo fread( $handle, 8192 );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
		}
	}
}
