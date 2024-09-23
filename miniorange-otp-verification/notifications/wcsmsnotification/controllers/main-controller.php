<?php
/**
 * Load controller for SMS Notifications view
 *
 * @package miniorange-otp-verification/Notifications
 */

use OTP\Notifications\WcSMSNotification\Handler\WooCommerceNotifications;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$registered      = WooCommerceNotifications::instance()->moAddOnV();
	$mo_current_user = wp_get_current_user();
	$wc_controller   = MSN_DIR . 'controllers/';
	$url             = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore -- false positive.
	$addon           = add_query_arg( array( 'page' => 'addon' ), remove_query_arg( 'addon', $url ) );

	require $wc_controller . 'wc-sms-notification.php';

