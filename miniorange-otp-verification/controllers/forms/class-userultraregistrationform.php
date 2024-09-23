<?php
/**
 * Load admin view for UserUltraRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\UserUltraRegistrationForm;

$handler            = UserUltraRegistrationForm::instance();
$uultra_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$uultra_hidden      = 'checked' === $uultra_enabled ? '' : 'hidden';
$uultra_enable_type = $handler->get_otp_type_enabled();
$uultra_form_list   = admin_url() . 'admin.php?page=userultra&tab=fields';
$uultra_field_key   = $handler->get_phone_key_details();
$uultra_type_phone  = $handler->get_phone_html_tag();
$uultra_type_email  = $handler->get_email_html_tag();
$uultra_type_both   = $handler->get_both_html_tag();
$form_name          = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/userultraregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
