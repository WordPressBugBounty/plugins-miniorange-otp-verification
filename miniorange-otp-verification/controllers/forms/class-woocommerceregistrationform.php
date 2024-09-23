<?php
/**
 * Load admin view for WooCommerceRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WooCommerceRegistrationForm;
use OTP\Helper\MoUtility;


$handler                                = WooCommerceRegistrationForm::instance();
$woocommerce_registration               = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wc_hidden                              = 'checked' === $woocommerce_registration ? '' : 'style=display:none';
$wc_enable_type                         = $handler->get_otp_type_enabled();
$wc_restrict_duplicates                 = (bool) $handler->restrict_duplicates() ? 'checked' : '';
$wc_reg_type_phone                      = $handler->get_phone_html_tag();
$wc_reg_type_email                      = $handler->get_email_html_tag();
$wc_reg_type_both                       = $handler->get_both_html_tag();
$form_name                              = $handler->get_form_name();
$redirect_page                          = $handler->redirectToPage();
$redirect_page_id                       = MoUtility::is_blank( $redirect_page ) ? '' : get_posts(
	array(
		'title'     => $redirect_page,
		'post_type' => 'page',
	)
)[0]->ID;
$is_ajax_form                           = $handler->is_ajax_form();
$is_ajax_mode_enabled                   = $is_ajax_form ? 'checked' : '';
$wc_button_text                         = $handler->get_button_text();
$is_redirect_after_registration_enabled = $handler->isredirectToPageEnabled() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/woocommerceregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
