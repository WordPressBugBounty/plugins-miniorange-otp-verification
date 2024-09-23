<?php
/**
 * Load admin view for SimplrRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\SimplrRegistrationForm;

$handler             = SimplrRegistrationForm::instance();
$simplr_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$simplr_hidden       = 'checked' === $simplr_enabled ? '' : 'hidden';
$simplr_enabled_type = $handler->get_otp_type_enabled();
$simplr_fields_page  = admin_url() . 'options-general.php?page=simplr_reg_set&regview=fields&orderby=name&order=desc';
$simplr_field_key    = $handler->get_phone_key_details();
$simplr_type_phone   = $handler->get_phone_html_tag();
$simplr_type_email   = $handler->get_email_html_tag();
$simplr_type_both    = $handler->get_both_html_tag();
$form_name           = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/simplrregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
