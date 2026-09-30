<?php
/**
 * Loads the Troubleshooting tab's subtabs.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

$subtab = MoUtility::get_current_page_parameter_value( 'subpage', 'errorCodesSubTab' );

if ( ! isset( $controller ) || ! is_string( $controller ) || empty( $controller ) ) {
	return;
}

$allowed_files = array(
	'error-codes.php',
	'troubleshooting-logs.php',
);

foreach ( $allowed_files as $file ) {
	$file_path = $controller . $file;
	if ( ! MoUtility::mo_require_file( $file_path, $controller ) ) {
		continue;
	}
	require $file_path;
}
