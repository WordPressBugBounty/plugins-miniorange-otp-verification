<?php
/**
 * Loads admin view for Edumareg.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\Edumareg;

$handler                      = Edumareg::instance();
$edumareg_enabled             = $handler->is_form_enabled() ? 'checked' : '';
$edumareg_hidden              = 'checked' === $edumareg_enabled ? '' : 'hidden';
$edumareg_enabled_type        = $handler->get_otp_type_enabled();
$edumareg_type_phone          = $handler->get_phone_html_tag();
$edumareg_phone_field_key     = $handler->get_phone_key_details();
$edumareg_type_email          = $handler->get_email_html_tag();
$form_name                    = $handler->get_form_name();
$edumareg_restrict_duplicates = $handler->restrict_duplicates() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/edumareg.php';
get_plugin_form_link( $handler->get_form_documents() );
