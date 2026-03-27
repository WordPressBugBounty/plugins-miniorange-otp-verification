<?php
/**
 * Load admin view for PaidMembershipForm.
 *
 * @package miniorange-otp-verification/controllers/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Handler\Forms\PaidMembershipForm;
use OTP\Helper\MoUtility;

$handler                   = PaidMembershipForm::instance();
$pmpro_enabled             = $handler->is_form_enabled() ? 'checked' : '';
$pmpro_hidden              = 'checked' === $pmpro_enabled ? '' : 'hidden';
$pmpro_enabled_type        = $handler->get_otp_type_enabled();
$pmpro_type_phone          = $handler->get_phone_html_tag();
$pmpro_type_email          = $handler->get_email_html_tag();
$form_name                 = $handler->get_form_name();
$pmpro_restrict_duplicates = $handler->restrict_duplicates() ? 'checked' : '';

$view_file_path = MOV_DIR . 'views/forms/mopaidmembershipform.php';
$view_loaded    = false;
if ( MoUtility::mo_require_file( $view_file_path, MOV_DIR ) && file_exists( $view_file_path ) ) {
	require $view_file_path;
	$view_loaded = true;
}
if ( ! $view_loaded && file_exists( $view_file_path ) ) {
	require $view_file_path;
	$view_loaded = true;
}
if ( ! $view_loaded ) {
	$plugin_root  = dirname( __FILE__, 3 );
	$view_fallback = $plugin_root . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'mopaidmembershipform.php';
	if ( file_exists( $view_fallback ) ) {
		require $view_fallback;
	}
}
get_plugin_form_link( $handler->get_form_documents() );