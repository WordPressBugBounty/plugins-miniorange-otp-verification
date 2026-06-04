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
use OTP\Helper\FormList;

// Validate admin handler exists before accessing.
if ( ! isset( $admin_handler ) || ! is_object( $admin_handler ) || ! method_exists( $admin_handler, 'get_nonce_value' ) ) {
	return;
}

// Validate $tab_details object exists and has required properties.
if ( ! isset( $tab_details ) || ! is_object( $tab_details ) || ! isset( $tab_details->tab_details ) ) {
	return;
}

// Validate ACCOUNT tab exists.
if ( ! isset( $tab_details->tab_details[ Tabs::ACCOUNT ] ) || ! isset( $tab_details->tab_details[ Tabs::ACCOUNT ]->menu_slug ) ) {
	return;
}

// Sanitize and validate REQUEST_URI from $_SERVER.
$server_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

// Ensure $server_uri is not empty before using remove_query_arg.
if ( empty( $server_uri ) ) {
	$server_uri = admin_url();
}

$request_uri = remove_query_arg( array( 'addon', 'form', 'subpage' ), $server_uri );
$profile_url = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::ACCOUNT ]->menu_slug ), $request_uri );
$help_url    = MoConstants::FAQ_URL;

$nonce = $admin_handler->get_nonce_value();
// Validate nonce is not empty.
if ( empty( $nonce ) || ! is_string( $nonce ) ) {
	return;
}

$is_logged_in     = MoUtility::micr();
$is_activated     = MoUtility::mclv();
$is_free_plugin   = strcmp( MOV_TYPE, 'MiniOrangeGateway' ) === 0;
$gateway_type     = get_mo_option( 'custome_gateway_type' );
$modal_notice_raw = get_mo_option( 'mo_transaction_notice' );
$modal_notice     = is_string( $modal_notice_raw ) ? json_decode( $modal_notice_raw, true ) : $modal_notice_raw;

// Validate and sanitize option values with proper type casting.
$remaining_email_option = get_mo_option( 'email_transactions_remaining' );
$remaining_email        = is_numeric( $remaining_email_option ) ? absint( $remaining_email_option ) : 0;

$remaining_sms_option = get_mo_option( 'phone_transactions_remaining' );
$remaining_sms        = is_numeric( $remaining_sms_option ) ? absint( $remaining_sms_option ) : 0;

$remaining_whatsapp_option = get_mo_option( 'whatsapp_transactions_remaining', 'mowp_customer_validation_' );
$remaining_whatsapp        = is_numeric( $remaining_whatsapp_option ) ? absint( $remaining_whatsapp_option ) : 0;

$smtp_enabled                = get_mo_option( 'smtp_enable_type' );
$whatsapp_enabled            = get_mo_option( 'mo_whatsapp_enable' );
$mo_whatsapp_type_enabled    = get_mo_option( 'mo_whatsapp_type' );
$mo_sms_as_backup            = get_mo_option( 'mo_sms_as_backup' );
$mo_whatsapp_gateway_enabled = $whatsapp_enabled && $mo_whatsapp_type_enabled && 'mo_whatsapp' === $mo_whatsapp_type_enabled;
$mo_smtp_enabled             = $is_free_plugin || ( $smtp_enabled && 'mo_smtp_enable' === $smtp_enabled );
$license_plan                = get_mo_option( 'customer_license_plan' );
$remaining_total_txn         = $remaining_email + $remaining_sms;
$active_class                = $remaining_total_txn < MoConstants::LOW_TRANSACTION_THRESHOLD ? 'mo-active-notice-bar' : '';
$mo_transactions             = null;
$now_ts                      = time();

// Compute transactions display text.
if ( $is_logged_in ) {
	if ( $mo_whatsapp_gateway_enabled ) {
		if ( $is_free_plugin || ( 'MoGateway' === $gateway_type && $mo_smtp_enabled ) ) {
			$mo_transactions = 'WhatsApp: ' . esc_attr( $remaining_whatsapp ) . '  |  SMS: ' . esc_attr( $remaining_sms ) . ' | Email: ' . esc_attr( $remaining_email );
		} elseif ( 'MoGateway' === $gateway_type && ! $mo_smtp_enabled ) {
			$mo_transactions = 'WhatsApp: ' . esc_attr( $remaining_whatsapp ) . '  |  SMS: ' . esc_attr( $remaining_sms );
		} elseif ( 'MoGateway' !== $gateway_type && $mo_smtp_enabled ) {
			$mo_transactions = 'WhatsApp: ' . esc_attr( $remaining_whatsapp ) . '  | Email: ' . esc_attr( $remaining_email );
		} else {
			$mo_transactions = 'WhatsApp: ' . esc_attr( $remaining_whatsapp );
		}
	} elseif ( $is_free_plugin ) {
		$mo_transactions = 'SMS: ' . esc_attr( $remaining_sms ) . ' | Email: ' . esc_attr( $remaining_email );
	} elseif ( 'MoGateway' === $gateway_type ) {
		if ( $mo_smtp_enabled ) {
			$mo_transactions = 'SMS: ' . esc_attr( $remaining_sms ) . ' | Email: ' . esc_attr( $remaining_email );
		} else {
			$mo_transactions = 'SMS: ' . esc_attr( $remaining_sms );
		}
	} elseif ( $mo_smtp_enabled ) {
		$mo_transactions = 'Email: ' . esc_attr( $remaining_email );
	}
}

$hidden               = is_null( $mo_transactions ) ? 'hidden' : '';
$sms_notice_dismissed = get_mo_option( 'mo_hide_sms_notice' );
$show_sms_notice      = ! ( is_numeric( $sms_notice_dismissed ) && ( $now_ts - (int) $sms_notice_dismissed ) < ( 7 * DAY_IN_SECONDS ) );

// Country restriction addon reminder is disabled — let users enable it on their own.
$should_show_sc = false;
$addon_sc_url   = '';

$curr_page           = MoUtility::get_current_page_parameter_value( 'page', '' );
$addon               = MoUtility::get_current_page_parameter_value( 'addon', '' );
$addon_settings_page = ( 'addon' === $curr_page && 'otp_spam_preventer' === $addon );
$req_url             = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

// Spam Preventer addon reminder variables.
$sp_last_dismissed = get_mo_option( 'mo_spam_preventer_modal_dismissed_ts' );
$sp_addon_dir      = trailingslashit( MOV_ADDON_DIR ) . 'otpspampreventer';
$sp_addon_present  = is_dir( $sp_addon_dir );
$sp_settings       = $sp_addon_present ? get_mo_option( 'mo_osp_settings' ) : null;
$sp_enabled        = is_array( $sp_settings ) && ! empty( $sp_settings['enabled'] );
$should_show_sp    = $sp_addon_present && ! $sp_enabled && ( empty( $sp_last_dismissed ) || ( (int) $now_ts - (int) $sp_last_dismissed ) > ( 3 * DAY_IN_SECONDS ) );
$addon_sp_url      = add_query_arg(
	array(
		'page'  => 'addon',
		'addon' => 'otp_spam_preventer',
	),
	$req_url
);

// Transaction Logs modal variables.
$tl_last_dismissed = get_mo_option( 'mo_transaction_logs_modal_dismissed_ts' );
$tl_enabled        = get_mo_option( 'is_mo_report_enabled' );

// Validate FormList class exists before instantiating.
if ( ! class_exists( 'OTP\Helper\FormList' ) ) {
	return;
}

$form_handler  = FormList::instance();
$enabled_forms = $form_handler->get_enabled_forms();
// Validate $enabled_forms is an array.
$enabled_forms     = is_array( $enabled_forms ) ? $enabled_forms : array();
$has_enabled_forms = ! empty( $enabled_forms );

// Check if transaction logs are explicitly enabled.
$tl_enabled_check = ( 1 === (int) $tl_enabled || '1' === $tl_enabled || true === $tl_enabled );

// Time check: show if never dismissed OR dismissed more than 7 days ago.
$tl_time_check = false;
if ( empty( $tl_last_dismissed ) ) {
	$tl_time_check = true;
} elseif ( is_numeric( $tl_last_dismissed ) && (int) $tl_last_dismissed > 0 ) {
	$time_diff = (int) $now_ts - (int) $tl_last_dismissed;
	if ( $time_diff > ( 7 * DAY_IN_SECONDS ) ) {
		$tl_time_check = true;
	}
}

$should_show_tl = ! $tl_enabled_check && $is_logged_in && $is_activated && ( 'moreporting' !== $curr_page ) && ! $addon_settings_page && $has_enabled_forms && $tl_time_check;
$reporting_url  = add_query_arg( array( 'page' => 'moreporting' ), $req_url );

// Low transaction alert variables.
$should_show_low_txn_alert = false;
$low_txn_threshold_key     = null;

// Validate $modal_notice is an array before processing.
if ( ! is_array( $modal_notice ) ) {
	$modal_notice = array();
}

if ( $is_logged_in && is_array( $modal_notice ) && ! empty( $modal_notice ) && ( $remaining_sms + $remaining_email ) <= 50 && ( 'MoGateway' === $gateway_type || $is_free_plugin ) ) {
	$remaining_total = $remaining_sms + $remaining_email;
	foreach ( $modal_notice as $key => $value ) {
		// Validate key and value are numeric before comparison.
		$key_int   = is_numeric( $key ) ? absint( $key ) : null;
		$value_int = is_numeric( $value ) ? absint( $value ) : null;

		if ( null !== $key_int && null !== $value_int && $remaining_total <= $value_int && $remaining_total >= $key_int ) {
			$should_show_low_txn_alert = true;
			$low_txn_threshold_key     = $key_int;
			break;
		}
	}
} else {
	// Initialize default modal notice array if not set or invalid.
	$array = array(
		'21' => '50',
		'2'  => '10',
		'0'  => '1',
	);
	update_mo_option( 'mo_transaction_notice', wp_json_encode( $array ) );
}

// Validate file path using MoUtility::mo_require_file() before including.
$view_file = MOV_DIR . 'views/titlebar.php';
if ( ! MoUtility::mo_require_file( $view_file, MOV_DIR ) ) {
	return;
}
require $view_file;
