<?php
/**
 * Load admin view for WooCommerceCheckOutForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WooCommerceCheckOutForm;
use OTP\Handler\Forms\MoWCCheckoutNew;

$handler                   = mo_is_block_based_checkout() ? MoWCCheckoutNew::instance() : WooCommerceCheckOutForm::instance();
$wc_checkout               = $handler->is_form_enabled() ? 'checked' : '';
$wc_checkout_hidden        = 'checked' === $wc_checkout ? '' : 'style=display:none';
$wc_checkout_enable_type   = $handler->get_otp_type_enabled();
$guest_checkout            = $handler->isGuestCheckoutOnlyEnabled() ? 'checked' : '';
$checkout_popup            = $handler->isPopUpEnabled() ? 'checked' : '';
$checkout_payment_plans    = $handler->getPaymentMethods();
$checkout_selection        = $handler->isSelectivePaymentEnabled() ? 'checked' : '';
$checkout_selection_hidden = 'checked' === $checkout_selection ? '' : 'hidden';
$wc_type_phone             = $handler->get_phone_html_tag();
$wc_type_email             = $handler->get_email_html_tag();
$button_text               = $handler->get_button_text();
$form_name                 = $handler->get_form_name();
$restrict_duplicates       = $handler->restrict_duplicates() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/mowoocommercecheckoutform.php';
get_plugin_form_link( $handler->get_form_documents() );
