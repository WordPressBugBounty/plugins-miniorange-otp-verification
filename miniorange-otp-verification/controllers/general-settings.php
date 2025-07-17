<?php
/**
 * Loads general settings tab.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

$hidden                    = 'generalSettingsSubTab' !== $subtab ? 'hidden' : '';
$otp_blocked_email_domains = get_mo_option( 'blocked_domains' );
$otp_blocked_phones        = get_mo_option( 'blocked_phone_numbers' );
$show_trans                = get_mo_option( 'show_remaining_trans' ) ? 'checked' : '';
$show_dropdown_on_form     = get_mo_option( 'show_dropdown_on_form' ) ? 'checked' : '';
$show_transaction_options  = MoUtility::is_mg();
$globallybanned_disabled   = apply_filters( 'set_class_exists_globallybanned', false ) && 'disabled' !== $disabled ? '' : 'disabled';

require MOV_DIR . 'views/general-settings.php';
