<?php
/**
 * Load admin view for WooCommerceProductVendors.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WooCommerceProductVendors;

$handler                   = WooCommerceProductVendors::instance();
$wc_pv_registration        = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wc_pv_hidden              = 'checked' === $wc_pv_registration ? '' : 'hidden';
$wc_pv_enable_type         = $handler->get_otp_type_enabled();
$wc_pv_restrict_duplicates = (bool) $handler->restrict_duplicates() ? 'checked' : '';
$wc_pv_reg_type_phone      = $handler->get_phone_html_tag();
$wc_pv_reg_type_email      = $handler->get_email_html_tag();
$wc_pv_reg_type_both       = $handler->get_both_html_tag();
$form_name                 = $handler->get_form_name();
$is_ajax_form              = $handler->is_ajax_form();
$is_ajax_mode_enabled      = $is_ajax_form ? 'checked' : '';
$wc_pv_button_text         = $handler->get_button_text();

require_once MOV_DIR . 'views/forms/woocommerceproductvendors.php';
get_plugin_form_link( $handler->get_form_documents() );
