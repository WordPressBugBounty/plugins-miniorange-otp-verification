<?php
/**
 * Loads admin view for CalderaForms.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\CalderaForms;

$handler                           = CalderaForms::instance();
$is_caldera_enabled                = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_caldera_hidden                 = 'checked' === $is_caldera_enabled ? '' : 'hidden';
$caldera_enabled_type              = $handler->get_otp_type_enabled();
$caldera_list_of_forms_otp_enabled = $handler->get_form_details();
$caldera_form_list                 = admin_url() . 'admin.php?page=caldera-forms';
$button_text                       = $handler->get_button_text();
$caldera_phone_type                = $handler->get_phone_html_tag();
$caldera_email_type                = $handler->get_email_html_tag();
$form_name                         = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/calderaforms.php';
get_plugin_form_link( $handler->get_form_documents() );
