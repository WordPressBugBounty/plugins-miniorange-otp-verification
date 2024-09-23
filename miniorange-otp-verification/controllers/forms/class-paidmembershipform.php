<?php
/**
 * Load admin view for PaidMembershipForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\PaidMembershipForm;

$handler            = PaidMembershipForm::instance();
$pmpro_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$pmpro_hidden       = 'checked' === $pmpro_enabled ? '' : 'hidden';
$pmpro_enabled_type = $handler->get_otp_type_enabled();
$pmpro_type_phone   = $handler->get_phone_html_tag();
$pmpro_type_email   = $handler->get_email_html_tag();
$form_name          = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/paidmembershipform.php';
get_plugin_form_link( $handler->get_form_documents() );
