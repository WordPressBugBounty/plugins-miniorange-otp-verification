<?php
/**
 * Loads admin view for ClassifyRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\ClassifyRegistrationForm;

$handler               = ClassifyRegistrationForm::instance();
$classify_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$classify_hidden       = 'checked' === $classify_enabled ? '' : 'hidden';
$classify_enabled_type = $handler->get_otp_type_enabled();
$classify_type_phone   = $handler->get_phone_html_tag();
$classify_type_email   = $handler->get_email_html_tag();
$form_name             = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/classifyregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
