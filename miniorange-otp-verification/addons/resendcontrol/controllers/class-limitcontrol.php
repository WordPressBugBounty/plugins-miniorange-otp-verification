<?php
/**
 * Limit OTP controller.
 *
 * @package miniorange-otp-verification/addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ROC\Handler\ResendControlAddonHandler;
use ROC\Handler\ResendControlHandler;
use OTP\Helper\MoConstants;
use OTP\Helper\MoFormDocs;

$otp_control_enable     = get_mo_rc_option( 'otp_control_enable' ) ? 'checked' : '';
$otp_control_hidden     = $otp_control_enable ? '' : 'hidden';
$otp_control_limit      = get_mo_rc_option( 'otp_control_limit' ) ? get_mo_rc_option( 'otp_control_limit' ) : 2;
$otp_control_time_block = get_mo_rc_option( 'otp_control_block_time' ) ? get_mo_rc_option( 'otp_control_block_time' ) : 2;
$handler                = ResendControlAddonHandler::instance();
$handler_custom         = ResendControlHandler::instance();
$nonce                  = $handler_custom->nonce;
$otp_timer_enable       = get_mo_rc_option( 'otp_timer_enable' ) ? 'checked' : '';
$otp_timer_hidden       = $otp_timer_enable ? '' : 'hidden';
$otp_timer              = get_mo_rc_option( 'otp_timer' ) ? get_mo_rc_option( 'otp_timer' ) : 2;
$option                 = $handler_custom->option;
$guide_link             = MoFormDocs::LIMIT_OTP_REQUEST_ADDON_LINK['guideLink'];

require MO_ROC_DIR . 'views/limitcontrol.php';
