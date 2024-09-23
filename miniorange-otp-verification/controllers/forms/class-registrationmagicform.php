<?php
/**
 * Load admin view for RegistrationMagicForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\RegistrationMagicForm;

$handler              = RegistrationMagicForm::instance();
$crf_enabled          = $handler->is_form_enabled() ? 'checked' : '';
$crf_hidden           = 'checked' === $crf_enabled ? '' : 'hidden';
$crf_enable_type      = $handler->get_otp_type_enabled();
$crf_form_list        = admin_url() . 'admin.php?page=rm_form_manage';
$crf_form_otp_enabled = $handler->get_form_details();
$crf_type_phone       = $handler->get_phone_html_tag();
$crf_type_email       = $handler->get_email_html_tag();
$crf_type_both        = $handler->get_both_html_tag();
$form_name            = $handler->get_form_name();
$restrict_duplicates  = $handler->restrict_duplicates() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/registrationmagicform.php';
get_plugin_form_link( $handler->get_form_documents() );
