<?php
/**
 * Loads admin view for CustomForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\CustomForm;

$nonce                       = $admin_handler->get_nonce_value();
$handler                     = CustomForm::instance();
$custom_form_submit_selector = $handler->getSubmitKeyDetails();
$custom_form_enabled         = '' !== $custom_form_submit_selector || empty( $custom_form_submit_selector );
$custom_form_otp_type        = get_mo_option( 'cf_enable_type', 'mo_otp_' );
$custom_form_field_selector  = $handler->get_field_key_details();
$custom_form_type_phone      = $handler->get_phone_html_tag();
$custom_form_type_email      = $handler->get_email_html_tag();
$button_text                 = $handler->get_button_text();

require_once MOV_DIR . 'views/customform.php';
