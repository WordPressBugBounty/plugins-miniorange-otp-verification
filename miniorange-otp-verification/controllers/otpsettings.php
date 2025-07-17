<?php
/**
 * Loads admin view for otpsettings tab.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoUtility;
use OTP\Helper\GatewayFunctions;

$hidden                   = 'otpSettingsSubTab' !== $subtab ? 'hidden' : '';
$show_trans               = get_mo_option( 'show_remaining_trans' ) ? 'checked' : '';
$show_dropdown_on_form    = get_mo_option( 'show_dropdown_on_form' ) ? 'checked' : '';
$mo_otp_length            = get_mo_option( 'otp_length' ) ? get_mo_option( 'otp_length' ) : 5;
$mo_otp_validity          = get_mo_option( 'otp_validity' ) ? get_mo_option( 'otp_validity' ) : 5;
$show_transaction_options = MoUtility::is_mg();
$nonce                    = $admin_handler->get_nonce_value();
$alphanumeric_disabled    = apply_filters( 'set_class_exists_aplhanumeric', false ) && 'disabled' !== $disabled ? '' : 'disabled';
$globallybanned_disabled  = apply_filters( 'set_class_exists_globallybanned', false ) && 'disabled' !== $disabled ? '' : 'disabled';
$master_otp_disabled      = apply_filters( 'set_class_exists_masterotp', false ) && 'disabled' !== $disabled ? '' : 'disabled';
$gateway                  = GatewayFunctions::instance();
require_once MOV_DIR . 'views/otpsettings.php';
