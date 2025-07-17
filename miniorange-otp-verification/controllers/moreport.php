<?php
/**
 * Load admin view for MoReport feature.
 *
 * @package miniorange-otp-verification
 */

use OTP\Helper\MoUtility;



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$current_date         = gmdate( 'Y-m-d H:i' );
$time                 = (int) $current_date - ( 60 * 60 );
$from_date            = gmdate( 'Y-m-d H:i', strtotime( '-1 days' ) );
$mle                  = MoUtility::mllc();
$disabled             = ( ! file_exists( MOV_DIR . 'helper/class-moreporting.php' ) || $mle['STATUS'] ) ? 'disabled' : '';
$show_notice          = ! file_exists( MOV_DIR . 'helper/class-moreporting.php' ) ? 'true' : '';
$nonce                = $admin_handler->get_nonce_value();
$is_mo_report_enabled = get_mo_option( 'is_mo_report_enabled' ) ? 'checked' : '';

require_once MOV_DIR . 'views/moreport.php';
