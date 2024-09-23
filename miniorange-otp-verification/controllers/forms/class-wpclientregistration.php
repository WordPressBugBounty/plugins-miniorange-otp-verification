<?php
/**
 * Load admin view for WPClientRegistration.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WPClientRegistration;


$handler               = WPClientRegistration::instance();
$wp_client_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$wp_client_hidden      = 'checked' === $wp_client_enabled ? '' : 'hidden';
$wp_client_enable_type = $handler->get_otp_type_enabled();
$wp_client_type_phone  = $handler->get_phone_html_tag();
$wp_client_type_email  = $handler->get_email_html_tag();
$wp_client_type_both   = $handler->get_both_html_tag();
$form_name             = $handler->get_form_name();
$restrict_duplicates   = $handler->restrict_duplicates() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/wpclientregistration.php';
get_plugin_form_link( $handler->get_form_documents() );
