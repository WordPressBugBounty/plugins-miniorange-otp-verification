<?php
/**
 * Loads admin view for ARmember Form.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\MoARMemberRegistrationForm;

$handler                            = MoARMemberRegistrationForm::instance();
$is_armember_enabled                = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_armember_hidden                 = 'checked' === $is_armember_enabled ? '' : 'style="display:none;"';
$armember_enabled_type              = $handler->get_otp_type_enabled();
$armember_list_of_forms_otp_enabled = $handler->get_form_details();
$armember_form_list                 = admin_url() . 'admin.php?page=arm_manage_forms';
$button_text                        = $handler->get_button_text();
$armember_phone_type                = $handler->get_phone_html_Tag();
$armember_email_type                = $handler->get_email_html_Tag();
$form_name                          = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/moarmemberregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
