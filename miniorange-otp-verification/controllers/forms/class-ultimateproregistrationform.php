<?php
/**
 * Load admin view for UltimateProRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\UltimateProRegistrationForm;

$handler                 = UltimateProRegistrationForm::instance();
$ultipro_enabled         = (bool) $handler->is_form_enabled() ? 'checked' : '';
$ultipro_hidden          = 'checked' === $ultipro_enabled ? '' : 'hidden';
$ultipro_enabled_type    = $handler->get_otp_type_enabled();
$umpro_custom_field_list = admin_url() . 'admin.php?page=ihc_manage&tab=register&subtab=custom_fields';
$umpro_type_phone        = $handler->get_phone_html_tag();
$umpro_type_email        = $handler->get_email_html_tag();
$form_name               = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/ultimateproregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
