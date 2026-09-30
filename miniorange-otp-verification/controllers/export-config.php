<?php
/**
 * Loads the Export Configuration admin view.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Handler\MoExportConfigHandler;
use OTP\Helper\MoUtility;

$ec_nonce = MoExportConfigHandler::instance()->get_nonce_value();

$view_file = MOV_DIR . 'views/export-config.php';
if ( ! MoUtility::mo_require_file( $view_file, MOV_DIR ) ) {
	return;
}
require $view_file;
