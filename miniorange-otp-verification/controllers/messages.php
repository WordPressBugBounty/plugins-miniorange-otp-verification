<?php
/**
 * Loads admin view for common message tab.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;

$nonce                = $admin_handler->get_nonce_value();
$msg_list             = MoMessages::get_original_message_list();
$frontend_msg_list    = MoMessages::get_frontend_message_list();
$common_msg_hidden    = 'messagesSubTab' !== $subtab ? 'hidden' : '';
$otp_success_email    = get_mo_option( 'success_email_message', 'mo_otp_' ) ? get_mo_option( 'success_email_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::OTP_SENT_EMAIL );
$otp_success_phone    = get_mo_option( 'success_phone_message', 'mo_otp_' ) ? get_mo_option( 'success_phone_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::OTP_SENT_PHONE );
$otp_error_phone      = get_mo_option( 'error_phone_message', 'mo_otp_' ) ? get_mo_option( 'error_phone_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_OTP_PHONE );
$otp_error_email      = get_mo_option( 'error_email_message', 'mo_otp_' ) ? get_mo_option( 'error_email_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_OTP_EMAIL );
$phone_invalid_format = get_mo_option( 'invalid_phone_message', 'mo_otp_' ) ? get_mo_option( 'invalid_phone_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_PHONE_FORMAT );
$email_invalid_format = get_mo_option( 'invalid_email_message', 'mo_otp_' ) ? get_mo_option( 'invalid_email_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_EMAIL_FORMAT );
$invalid_otp          = MoUtility::get_invalid_otp_method();
$otp_blocked_email    = get_mo_option( 'blocked_email_message', 'mo_otp_' ) ? get_mo_option( 'blocked_email_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_EMAIL_BLOCKED );
$otp_blocked_phone    = get_mo_option( 'blocked_phone_message', 'mo_otp_' ) ? get_mo_option( 'blocked_phone_message', 'mo_otp_' ) : MoMessages::showMessage( MoMessages::ERROR_PHONE_BLOCKED );
$phone_entries        = array(
	'OTP_SENT_PHONE'      => array(
		'old_msg' => $msg_list['OTP_SENT_PHONE'],
		'new_msg' => $otp_success_phone,
		'label'   => 'Success OTP Message',
	),
	'ERROR_OTP_PHONE'     => array(
		'old_msg' => $msg_list['ERROR_OTP_PHONE'],
		'new_msg' => $otp_error_phone,
		'label'   => 'Error OTP Message',
	),
	'ERROR_PHONE_FORMAT'  => array(
		'old_msg' => $msg_list['ERROR_PHONE_FORMAT'],
		'new_msg' => $phone_invalid_format,
		'label'   => 'Invalid Format Message',
	),
	'ERROR_PHONE_BLOCKED' => array(
		'old_msg' => $msg_list['ERROR_PHONE_BLOCKED'],
		'new_msg' => $otp_blocked_phone,
		'label'   => 'Blocked Number Message',
	),
);

$email_entries = array(
	'OTP_SENT_EMAIL'      => array(
		'old_msg' => $msg_list['OTP_SENT_EMAIL'],
		'new_msg' => $otp_success_email,
		'label'   => 'Success OTP Message',
	),
	'ERROR_OTP_EMAIL'     => array(
		'old_msg' => $msg_list['ERROR_OTP_EMAIL'],
		'new_msg' => $otp_error_email,
		'label'   => 'Error OTP Message',
	),
	'ERROR_EMAIL_FORMAT'  => array(
		'old_msg' => $msg_list['ERROR_EMAIL_FORMAT'],
		'new_msg' => $email_invalid_format,
		'label'   => 'Invalid Format Message',
	),
	'ERROR_EMAIL_BLOCKED' => array(
		'old_msg' => $msg_list['ERROR_EMAIL_BLOCKED'],
		'new_msg' => $otp_blocked_email,
		'label'   => 'Blocked Email Message',
	),
);

$custom_entries = array(
	'INVALID_OTP' => array(
		'old_msg' => $msg_list['INVALID_OTP'],
		'new_msg' => $invalid_otp,
		'label'   => 'Invalid OTP Message',
	),
);

$combined_message_array = array_merge( $phone_entries, $email_entries, $custom_entries );

foreach ( $msg_list as $key => $value ) {
	if ( get_mo_option( $key, 'mo_otp_' ) && ! isset( $combined_message_array[ $key ] ) ) {
		$custom_entries[ $key ] = array(
			'old_msg' => $msg_list[ $key ],
			'new_msg' => get_mo_option( $key, 'mo_otp_' ),
			'label'   => 'Custom Messgae',
		);
	}
}

$final_message_array = array_merge( $combined_message_array, $custom_entries );

/**
 * Define a regular expression pattern to match any special characters
 *
 * @param string $sentence sentence to be checked.
 * @return boolean
 */
function has_special_characters( $sentence ) {
	$pattern = '/[<>]/';
	return preg_match( $pattern, $sentence ) === 1;
}
$reduced_msg_list = array_diff_key( $frontend_msg_list, $final_message_array );

foreach ( $reduced_msg_list as $key => $value ) {
	if ( has_special_characters( $value ) ) {
		unset( $reduced_msg_list[ $key ] );
	}
}
require_once MOV_DIR . 'views/messages.php';
