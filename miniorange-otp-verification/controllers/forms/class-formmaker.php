<?php
/**
 * Load admin view for ElementorProFormFree.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\FormMaker;

$handler                      = FormMaker::instance();
$form_maker_form_enabled      = (bool) $handler->is_form_enabled() ? 'checked' : '';
$form_maker_form_hidden       = 'checked' === $form_maker_form_enabled ? '' : 'hidden';
$formmaker_form_list          = admin_url() . 'admin.php?page=manage_fm';
$form_maker_form_enabled_type = $handler->get_otp_type_enabled();
$form_maker_form_type_email   = $handler->get_email_html_tag();
$form_maker_form_type_phone   = $handler->get_phone_html_tag();
$form_maker_form_otp_enabled  = $handler->get_form_details();
$form_name                    = $handler->get_form_name();
$button_text                  = $handler->get_button_text();

require_once MOV_DIR . 'views/forms/formmaker.php';
get_plugin_form_link( $handler->get_form_documents() );
