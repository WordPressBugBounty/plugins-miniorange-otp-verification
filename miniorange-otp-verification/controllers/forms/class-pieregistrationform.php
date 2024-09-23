<?php
/**
 * Load admin view for PieRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\PieRegistrationForm;

$handler         = PieRegistrationForm::instance();
$pie_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$pie_hidden      = 'checked' === $pie_enabled ? '' : 'hidden';
$pie_enable_type = $handler->get_otp_type_enabled();
$pie_field_key   = $handler->get_phone_key_details();
$pie_type_phone  = $handler->get_phone_html_tag();
$pie_type_email  = $handler->get_email_html_tag();
$pie_type_both   = $handler->get_both_html_tag();
$form_name       = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/pieregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
