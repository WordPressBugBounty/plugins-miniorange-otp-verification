<?php
/**
 * Loads the Troubleshooting > Error Codes subtab view.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

$hidden = 'errorCodesSubTab' !== $subtab ? 'hidden' : '';

$view_file = MOV_DIR . 'views/error-codes.php';
if ( ! MoUtility::mo_require_file( $view_file, MOV_DIR ) ) {
	return;
}
require $view_file;
