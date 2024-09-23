<?php
/**
 * Loads admin view for DocDirectThemeRegistration.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\DocDirectThemeRegistration;

$handler                = DocDirectThemeRegistration::instance();
$docdirect_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$docdirect_hidden       = 'checked' === $docdirect_enabled ? '' : 'hidden';
$docdirect_enabled_type = $handler->get_otp_type_enabled();
$docdirect_type_phone   = $handler->get_phone_html_tag();
$docdirect_type_email   = $handler->get_email_html_tag();
$form_name              = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/docdirectthemeregistration.php';
get_plugin_form_link( $handler->get_form_documents() );
