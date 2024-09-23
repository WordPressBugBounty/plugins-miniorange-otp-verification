<?php
/**
 * Load admin view for WPFormsPlugin.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WPFormsPlugin;

$handler                          = WPFormsPlugin::instance();
$is_wpform_enabled                = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_wpform_hidden                 = 'checked' === $is_wpform_enabled ? '' : 'style="display:none;"';
$wpform_enabled_type              = $handler->get_otp_type_enabled();
$wpform_list_of_forms_otp_enabled = $handler->get_form_details();
$wpform_form_list                 = admin_url() . 'admin.php?page=wpforms-overview';
$button_text                      = $handler->get_button_text();
$wpform_phone_type                = $handler->get_phone_html_tag();
$wpform_email_type                = $handler->get_email_html_tag();
$wpform_both_type                 = $handler->get_both_html_tag();
$form_name                        = $handler->get_form_name();
$enter_otp_text                   = $handler->get_enter_otp_field_text();
$verify_button_text               = $handler->get_verify_button_text();

require_once MOV_DIR . 'views/forms/wpformsplugin.php';
get_plugin_form_link( $handler->get_form_documents() );
