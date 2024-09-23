<?php
/**
 * Loads admin view for Edumalog.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\Edumalog;

$handler                  = Edumalog::instance();
$edumalog_enabled         = $handler->is_form_enabled() ? 'checked' : '';
$edumalog_hidden          = 'checked' === $edumalog_enabled ? '' : 'hidden';
$edumalog_enabled_type    = $handler->get_otp_type_enabled();
$edumalog_type_phone      = $handler->get_phone_html_tag();
$edumalog_type_email      = $handler->get_email_html_tag();
$edumalog_phone_field_key = $handler->get_phone_key_details();
$form_name                = $handler->get_form_name();
$edumalog_log_bypass      = $handler->byPassCheckForAdmins() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/edumalog.php';
get_plugin_form_link( $handler->get_form_documents() );
