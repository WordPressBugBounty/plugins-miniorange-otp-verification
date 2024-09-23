<?php
/**
 * Load admin view for VisualFormBuilder.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\VisualFormBuilder;

$handler                  = VisualFormBuilder::instance();
$visual_form_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$visual_form_hidden       = 'checked' === $visual_form_enabled ? '' : 'hidden';
$visual_form_enabled_type = $handler->get_otp_type_enabled();
$visual_form_list         = admin_url() . 'admin.php?page=visual-form-builder';
$visual_form_otp_enabled  = $handler->get_form_details();
$visual_form_type_phone   = $handler->get_phone_html_tag();
$visual_form_type_email   = $handler->get_email_html_tag();
$button_text              = $handler->get_button_text();
$form_name                = $handler->get_form_name();
$enter_otp_text           = $handler->get_enter_otp_field_text();

require_once MOV_DIR . 'views/forms/visualformbuilder.php';
get_plugin_form_link( $handler->get_form_documents() );
