<?php
/**
 * Loads admin view for FormCraftBasicForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\FormCraftBasicForm;

$handler                = FormCraftBasicForm::instance();
$formcraft_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$formcraft_hidden       = 'checked' === $formcraft_enabled ? '' : 'hidden';
$formcraft_enabled_type = $handler->get_otp_type_enabled();
$formcraft_list         = admin_url() . 'admin.php?page=formcraft_basic_dashboard';
$formcraft_otp_enabled  = $handler->get_form_details();
$formcraft_type_phone   = $handler->get_phone_html_tag();
$formcraft_type_email   = $handler->get_email_html_tag();
$form_name              = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/formcraftbasicform.php';
get_plugin_form_link( $handler->get_form_documents() );
