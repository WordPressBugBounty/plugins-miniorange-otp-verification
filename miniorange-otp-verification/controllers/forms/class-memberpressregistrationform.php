<?php
/**
 * Load admin view for MemberPressRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\MemberPressRegistrationForm;

$handler            = MemberPressRegistrationForm::instance();
$mrp_registration   = $handler->is_form_enabled() ? 'checked' : '';
$mrp_default_hidden = 'checked' === $mrp_registration ? '' : 'hidden';
$mrp_default_type   = $handler->get_otp_type_enabled();
$mrp_field_key      = $handler->get_phone_key_details();
$mrp_fields         = admin_url() . 'admin.php?page=memberpress-options#mepr-fields';
$mrpreg_phone_type  = $handler->get_phone_html_tag();
$mrpreg_email_type  = $handler->get_email_html_tag();
$mrpreg_both_type   = $handler->get_both_html_tag();
$form_name          = $handler->get_form_name();
$mpr_anon_only      = $handler->bypass_for_logged_in_users() ? 'checked' : '';

require_once MOV_DIR . 'views/forms/memberpressregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
