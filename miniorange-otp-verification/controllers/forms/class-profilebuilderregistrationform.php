<?php
/**
 * Load admin view for ProfileBuilderRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\ProfileBuilderRegistrationForm;

$handler           = ProfileBuilderRegistrationForm::instance();
$pb_enabled        = $handler->is_form_enabled() ? 'checked' : '';
$pb_hidden         = 'checked' === $pb_enabled ? '' : 'hidden';
$pb_enable_type    = $handler->get_otp_type_enabled();
$pb_phone_key      = $handler->get_phone_key_details();
$pb_fields         = admin_url() . 'admin.php?page=manage-fields';
$pb_reg_type_phone = $handler->get_phone_html_tag();
$pb_reg_type_email = $handler->get_email_html_tag();
$pb_reg_type_both  = $handler->get_both_html_tag();
$form_name         = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/profilebuilderregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );

