<?php
/**
 * Loads the Troubleshooting > Logs subtab view.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Handler\MoTroubleshootingHandler;
use OTP\Helper\MoLogger;
use OTP\Helper\MoUtility;

$hidden = 'logsSubTab' !== $subtab ? 'hidden' : '';

$nonce              = MoTroubleshootingHandler::instance()->get_nonce_value();
$debug_logs_enabled = (bool) get_mo_option( 'enable_debug_logs' );
$log_file_exists    = MoLogger::log_file_exists();
$log_file_size      = MoLogger::get_log_size();
$log_last_modified  = MoLogger::get_log_last_modified();
$download_log_url   = wp_nonce_url( admin_url( 'admin-post.php?action=mo_download_debug_log' ), $nonce );

$view_file = MOV_DIR . 'views/troubleshooting-logs.php';
if ( ! MoUtility::mo_require_file( $view_file, MOV_DIR ) ) {
	return;
}
require $view_file;
