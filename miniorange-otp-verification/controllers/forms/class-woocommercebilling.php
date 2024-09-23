<?php
/**
 * Load admin view for WooCommerceBilling.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WooCommerceBilling;

$handler                 = WooCommerceBilling::instance();
$wc_billing_enable       = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wc_billing_hidden       = 'checked' === $wc_billing_enable ? '' : 'hidden';
$wc_billing_type_enabled = $handler->get_otp_type_enabled();
$wc_billing_type_phone   = $handler->get_phone_html_tag();
$wc_billing_type_email   = $handler->get_email_html_tag();
$wc_restrict_duplicates  = (bool) $handler->restrict_duplicates() ? 'checked' : '';
$button_text             = $handler->get_button_text();
$form_name               = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/woocommercebilling.php';
get_plugin_form_link( $handler->get_form_documents() );
