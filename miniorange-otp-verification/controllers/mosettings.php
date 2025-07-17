<?php
/**
 * Load admin view for settings tab.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

$nonce  = $admin_handler->get_nonce_value();
$subtab = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : 'generalSettingsSubTab'; //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.

require $controller . 'general-settings.php';
require $controller . 'otpsettings.php';
require $controller . 'messages.php';
require $controller . 'design.php';
