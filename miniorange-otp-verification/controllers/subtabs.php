<?php
/**
 * Load view for subtabs
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Objects\Tabs;
use OTP\Helper\MoUtility;

$request_uri    = remove_query_arg( array( 'addon', 'form', 'subpage' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); // phpcs:ignore -- false positive.
$profile_url    = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::ACCOUNT ]->menu_slug ), $request_uri );
$help_url       = MoConstants::FAQ_URL;
$register_msg   = MoMessages::showMessage( MoMessages::REGISTER_WITH_US, array( 'url' => $profile_url ) ); //TODO: Check if this is being used
$activation_msg = MoMessages::showMessage( MoMessages::ACTIVATE_PLUGIN, array( 'url' => $profile_url ) );
$gateway_msg    = MoMessages::showMessage( MoMessages::CONFIG_GATEWAY, array( 'url' => $gateway_url ) );
$active_tab     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the tab name, doesn't require nonce verification.
$license_url    = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::PRICING ]->menu_slug ), $request_uri );
$nonce          = $admin_handler->get_nonce_value();
$is_logged_in   = MoUtility::micr();
$is_free_plugin = strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0;

require MOV_DIR . 'views/subtabs.php';
