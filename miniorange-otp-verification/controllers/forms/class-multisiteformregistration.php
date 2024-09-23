<?php
/**
 * Load admin view for MultiSiteFormRegistration.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\MultiSiteFormRegistration;

$handler                = MultiSiteFormRegistration::instance();
$multisite_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$multisite_hidden       = 'checked' === $multisite_enabled ? '' : 'hidden';
$multisite_enabled_type = $handler->get_otp_type_enabled();

$multisite_type_phone = $handler->get_phone_html_tag();
$multisite_type_email = $handler->get_email_html_tag();
$form_name            = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/multisiteformregistration.php';
get_plugin_form_link( $handler->get_form_documents() );
