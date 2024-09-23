<?php
/**
 * Load admin view for UserProRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\UserProRegistrationForm;

$handler                = UserProRegistrationForm::instance();
$userpro_enabled        = $handler->is_form_enabled() ? 'checked' : '';
$userpro_hidden         = 'checked' === $userpro_enabled ? '' : 'hidden';
$userpro_enabled_type   = $handler->get_otp_type_enabled();
$userpro_field_list     = admin_url() . 'admin.php?page=userpro&tab=fields';
$automatic_verification = $handler->disable_auto_activation() ? 'checked' : '';
$userpro_type_phone     = $handler->get_phone_html_tag();
$userpro_type_email     = $handler->get_email_html_tag();
$form_name              = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/userproregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
