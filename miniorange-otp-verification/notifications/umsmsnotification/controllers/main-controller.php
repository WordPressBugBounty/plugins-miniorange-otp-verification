<?php
/**
 * Main Controller of Ultimate member SMS notifications.
 *
 * @package miniorange-otp-verification/addons/umsmsnotification/controllers
 */

use OTP\Notifications\UmSMSNotification\Handler\UltimateMemberSMSNotificationsHandler;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$handler         = UltimateMemberSMSNotificationsHandler::instance();
$registered      = $handler->moAddOnV();
$mo_current_user = wp_get_current_user();
$um_controller   = UMSN_DIR . 'controllers/';
$addon           = add_query_arg( array( 'page' => 'addon' ), remove_query_arg( 'addon', ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ) );

require $um_controller . 'um-sms-notification.php';
