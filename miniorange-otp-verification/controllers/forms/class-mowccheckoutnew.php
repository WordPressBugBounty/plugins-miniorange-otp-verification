<?php
/**
 * Load admin view for MoWCCheckoutNew.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\MoWCCheckoutNew;

$handler                          = MoWCCheckoutNew::instance();
$wc_new_checkout                  = $handler->is_form_enabled() ? 'checked' : '';
$wc_new_checkout_hidden           = 'checked' === $wc_new_checkout ? '' : 'style=display:none';
$wc_new_guest_checkout            = $handler->isGuestCheckoutOnlyEnabled() ? 'checked' : '';
$wc_new_checkout_enable_type      = $handler->get_otp_type_enabled();
$wc_new_type_phone                = $handler->get_phone_html_tag();
$wc_new_type_email                = $handler->get_email_html_tag();
$form_name                        = $handler->get_form_name();
$wc_new_checkout_selection        = $handler->isSelectivePaymentEnabled() ? 'checked' : '';
$wc_new_checkout_selection_hidden = 'checked' === $wc_new_checkout_selection ? '' : 'hidden';
$checkout_payment_plans           = $handler->getPaymentMethods();
$wc_new_checkout_popup            = $handler->isPopUpEnabled() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/mowccheckoutnew.php';
get_plugin_form_link( $handler->get_form_documents() );
