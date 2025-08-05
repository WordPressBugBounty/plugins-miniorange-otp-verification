<?php
/**
 * Titlebar controller.
 *
 * @package miniorange-otp-verification/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Objects\Tabs;
use OTP\Helper\MoUtility;

$request_uri                 = remove_query_arg( array( 'addon', 'form', 'subpage' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); // phpcs:ignore -- false positive.
$profile_url                 = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::ACCOUNT ]->menu_slug ), $request_uri );
$help_url                    = MoConstants::FAQ_URL;
$nonce                       = $admin_handler->get_nonce_value();
$is_logged_in                = MoUtility::micr();
$is_free_plugin              = strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0;
$gateway_type                = get_mo_option( 'custome_gateway_type' );
$modal_notice                = get_mo_option( 'mo_transaction_notice' );
$remaining_email             = get_mo_option( 'email_transactions_remaining' );
$remaining_sms               = get_mo_option( 'phone_transactions_remaining' );
$remaining_whatsapp          = get_mo_option( 'whatsapp_transactions_remaining', 'mowp_customer_validation_' );
$smtp_enabled                = get_mo_option( 'smtp_enable_type' );
$whatsapp_enabled            = get_mo_option( 'mo_whatsapp_enable' );
$mo_whatsapp_type_enabled    = get_mo_option( 'mo_whatsapp_type' );
$mo_sms_as_backup            = get_mo_option( 'mo_sms_as_backup' );
$mo_whatsapp_gateway_enabled = $whatsapp_enabled && $mo_whatsapp_type_enabled && 'mo_whatsapp' === $mo_whatsapp_type_enabled;
$mo_smtp_enabled             = $is_free_plugin || ( $smtp_enabled && 'mo_smtp_enable' === $smtp_enabled );
$license_plan                = get_mo_option( 'customer_license_plan' );
$remaining_total_txn         = $remaining_email + $remaining_sms;
$active_class                = $remaining_total_txn < 15 ? 'mo-active-notice-bar' : '';
$mo_transactions             = null;

require MOV_DIR . 'views/titlebar.php';
