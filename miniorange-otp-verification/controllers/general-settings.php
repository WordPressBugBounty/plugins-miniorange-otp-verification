<?php
/**
 * Loads general settings tab.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

$hidden                            = 'generalSettingsSubTab' !== $subtab ? 'hidden' : '';
$otp_blocked_email_domains         = get_mo_option( 'blocked_domains' );
$otp_blocked_phones                = get_mo_option( 'blocked_phone_numbers' );
$show_trans                        = get_mo_option( 'show_remaining_trans' ) ? 'checked' : '';
$show_dropdown_on_form             = get_mo_option( 'show_dropdown_on_form' ) ? 'checked' : '';
$mo_phone_fallback_to_email        = get_mo_option( 'mo_phone_fallback_to_email' ) ? 'checked' : '';
$mo_phone_fallback_email_field_ids = get_mo_option( 'mo_phone_fallback_email_field_ids' );
$show_transaction_options          = MoUtility::is_mg();
$globallybanned_disabled           = apply_filters( 'set_class_exists_globallybanned', false ) && 'disabled' !== $disabled ? '' : 'disabled';
$test_mode                 		   = get_mo_option( 'test_mode' ) ? 'checked' : '';
$fail_mode                 		   = get_mo_option( 'fail_mode' ) ? 'checked' : '';

$view_file = MOV_DIR . 'views/general-settings.php';
if ( ! MoUtility::mo_require_file( $view_file, MOV_DIR ) ) {
	return;
}
require $view_file;
