<?php
/**
 * Load admin view for WpEmemberForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WpEmemberForm;

$handler             = WpEmemberForm::instance();
$emember_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$emember_hidden      = 'checked' === $emember_enabled ? '' : 'hidden';
$emember_enable_type = $handler->get_otp_type_enabled();
$form_settings_link  = admin_url() . 'admin.php?page=eMember_settings_menu&tab=4';
$emember_type_phone  = $handler->get_phone_html_tag();
$emember_type_email  = $handler->get_email_html_tag();
$emember_type_both   = $handler->get_both_html_tag();
$form_name           = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/wpememberform.php';
get_plugin_form_link( $handler->get_form_documents() );
