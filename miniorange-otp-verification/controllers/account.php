<?php
/**
 * Loads view for accounts tab.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\MoRegistrationHandler;
use OTP\Helper\MoConstants;
use OTP\Helper\MoUtility;

$url = MOV_PORTAL . '/viewlicense';

$handler = MoRegistrationHandler::instance();

if ( get_mo_option( 'registration_status' ) === 'MO_OTP_DELIVERED_SUCCESS'
		|| get_mo_option( 'registration_status' ) === 'MO_OTP_VALIDATION_FAILURE'
		|| get_mo_option( 'registration_status' ) === 'MO_OTP_DELIVERED_FAILURE' ) {
	$admin_phone = get_mo_option( 'admin_phone' ) ? get_mo_option( 'admin_phone' ) : '';
	$nonce       = $handler->get_nonce_value();
	require_once MOV_DIR . 'views/account/verify.php';
} elseif ( get_mo_option( 'verify_customer' ) ) {
	$admin_email = get_mo_option( 'admin_email' ) ? get_mo_option( 'admin_email' ) : '';
	$nonce       = $handler->get_nonce_value();
	require_once MOV_DIR . 'views/account/login.php';
} elseif ( ! MoUtility::micr() ) {
	$mo_current_user = wp_get_current_user();
	$admin_phone     = get_mo_option( 'admin_phone' ) ? get_mo_option( 'admin_phone' ) : '';
	$nonce           = $handler->get_nonce_value();
	delete_site_option( 'password_mismatch' );
	update_mo_option( 'new_registration', 'true' );
	require_once MOV_DIR . 'views/account/register.php';
} elseif ( MoUtility::micr() && ! MoUtility::mclv() ) {
	$nonce = $handler->get_nonce_value();
	require_once MOV_DIR . 'views/account/verify-lk.php';
} else {
	$customer_id = get_mo_option( 'admin_customer_key' );
	$api_key     = get_mo_option( 'admin_api_key' );
	$token       = get_mo_option( 'customer_token' );
	$vl          = MoUtility::mclv() && ! MoUtility::is_mg();
	$nonce       = $admin_handler->get_nonce_value();
	$regnonce    = $handler->get_nonce_value();
	require_once MOV_DIR . 'views/account/profile.php';
}
