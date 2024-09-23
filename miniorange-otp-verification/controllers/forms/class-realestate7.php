<?php
/**
 * Load admin view for RealEstate7.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\RealEstate7;

$handler                 = RealEstate7::instance();
$realestate_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$realestate_hidden       = 'checked' === $realestate_enabled ? '' : 'hidden';
$realestate_enabled_type = $handler->get_otp_type_enabled();
$realestate_type_phone   = $handler->get_phone_html_tag();
$realestate_type_email   = $handler->get_email_html_tag();
$form_name               = $handler->get_form_name();
$verify_button_text      = $handler->get_verify_button_text();

require_once MOV_DIR . 'views/forms/realestate7.php';
get_plugin_form_link( $handler->get_form_documents() );
