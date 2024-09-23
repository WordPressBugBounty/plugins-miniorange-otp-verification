<?php
/**
 * Load admin view for Easy Registration form.
 *
 * @package miniorange-otp-verification/views
 */

use OTP\Handler\Forms\EasyRegForm;

$handler                           = EasyRegForm::instance();
$is_easyreg_enabled                = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_easyreg_hidden                 = 'checked' === $is_easyreg_enabled ? '' : 'hidden';
$easyreg_enabled_type              = $handler->get_otp_type_enabled();
$easyreg_list_of_forms_otp_enabled = $handler->get_form_details();
$easyreg_form_list                 = admin_url() . 'admin.php?page=erforms-overview';
$button_text                       = $handler->get_button_text();
$easyreg_phone_type                = $handler->get_phone_html_tag();
$easyreg_email_type                = $handler->get_email_html_tag();
$form_name                         = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/easyregform.php';
get_plugin_form_link( $handler->get_form_documents() );
