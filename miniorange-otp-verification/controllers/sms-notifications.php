<?php
/**
 * Load view for SMS Notifications List
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$notif_folder = MOV_DIR . 'notifications/';

$subtab = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : 'MowcNotifSubTab'; //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.

require $notif_folder . 'wcsmsnotification/controllers/main-controller.php';
require $notif_folder . 'umsmsnotification/controllers/main-controller.php';
require MOV_DIR . '/controllers/premium-notif-controller.php';

