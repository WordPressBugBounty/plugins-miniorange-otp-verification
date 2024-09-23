<?php
/**
 * Load admin view for UserProfileMadeEasyRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\UserProfileMadeEasyRegistrationForm;

$handler          = UserProfileMadeEasyRegistrationForm::instance();
$upme_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$upme_hidden      = 'checked' === $upme_enabled ? '' : 'hidden';
$upme_enable_type = $handler->get_otp_type_enabled();
$upme_field_list  = admin_url() . 'admin.php?page=upme-field-customizer';
$upme_field_key   = $handler->get_phone_key_details();
$upme_type_phone  = $handler->get_phone_html_tag();
$upme_type_email  = $handler->get_email_html_tag();
$upme_type_both   = $handler->get_both_html_tag();
$form_name        = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/userprofilemadeeasyregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
