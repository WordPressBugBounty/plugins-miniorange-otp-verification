<?php
/**
 * Handles Troubleshooting tab actions: toggling debug logging, downloading
 * the log file, and clearing it.
 *
 * @package miniorange-otp-verification/handler
 */

namespace OTP\Handler;

use OTP\Helper\MoLogger;
use OTP\Helper\MoMessages;
use OTP\Objects\BaseActionHandler;
use OTP\Traits\Instance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MoTroubleshootingHandler' ) ) {
	/**
	 * MoTroubleshootingHandler class.
	 */
	class MoTroubleshootingHandler extends BaseActionHandler {

		use Instance;

		/** Registers the download action and the on-page form dispatcher. */
		protected function __construct() {
			parent::__construct();
			$this->nonce = 'mo_troubleshooting_nonce';
			add_action( 'admin_post_mo_download_debug_log', array( $this, 'handle_download_log' ) );
			add_action( 'admin_init', array( $this, 'handle_form_actions' ), 1 );
		}

		/**
		 * Handles the "Download Log" admin-post action.
		 *
		 * @return void
		 */
		public function handle_download_log() {
			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_html( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}
			MoLogger::download_log();
		}

		/**
		 * Handles the on-page "Save Settings" and "Clear Log" form posts,
		 * matching the option-dispatch pattern used by the plugin's other
		 * settings tabs.
		 *
		 * @return void
		 */
		public function handle_form_actions() {
			$action = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			if ( ! in_array( $action, array( 'mo_save_troubleshooting_logs', 'mo_clear_debug_logs' ), true ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( $this->nonce ) ) {
				wp_die( esc_html( MoMessages::showMessage( MoMessages::INVALID_OP ) ) );
			}

			if ( 'mo_save_troubleshooting_logs' === $action ) {
				update_mo_option( 'enable_debug_logs', isset( $_POST['mo_enable_debug_logs'] ) ? 1 : 0 );
			} else {
				MoLogger::clear_logs();
			}

			do_action( 'mo_registration_show_message', MoMessages::showMessage( MoMessages::EXTRA_SETTINGS_SAVED ), 'SUCCESS' );
		}
	}
}
