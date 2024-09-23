<?php
/**
 * Load admin view for FormidableForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\FormidableForm;

$handler               = FormidableForm::instance();
$frm_form_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$frm_form_hidden       = 'checked' === $frm_form_enabled ? '' : 'hidden';
$frm_form_enabled_type = $handler->get_otp_type_enabled();
$frm_form_list         = admin_url() . 'admin.php?page=formidable';
$frm_form_otp_enabled  = $handler->get_form_details();
$frm_form_type_phone   = $handler->get_phone_html_tag();
$frm_form_type_email   = $handler->get_email_html_tag();
$button_text           = $handler->get_button_text();
$form_name             = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/formidableform.php';
get_plugin_form_link( $handler->get_form_documents() );
