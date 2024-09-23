<?php
/**
 * Load admin view for FormidableForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\ForminatorForm;

$handler                              = ForminatorForm::instance();
$is_forminator_enabled                = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_forminator_hidden                 = 'checked' === $is_forminator_enabled ? '' : 'hidden';
$forminator_enabled_type              = $handler->get_otp_type_enabled();
$forminator_list_of_forms_otp_enabled = $handler->get_form_details();
$forminator_form_list                 = admin_url() . 'admin.php?page=forminator-cform';
$button_text                          = $handler->get_button_text();
$forminator_phone_type                = $handler->get_phone_html_tag();
$forminator_email_type                = $handler->get_email_html_tag();
$form_name                            = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/forminatorform.php';
get_plugin_form_link( $handler->get_form_documents() );
