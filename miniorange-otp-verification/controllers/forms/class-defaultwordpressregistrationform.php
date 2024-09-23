<?php
/**
 * Loads admin view for DefaultWordPressRegistrationForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\DefaultWordPressRegistrationForm;

$handler                  = DefaultWordPressRegistrationForm::instance();
$default_registration     = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wp_default_hidden        = 'checked' === $default_registration ? '' : 'style=display:none';
$wp_default_type          = $handler->get_otp_type_enabled();
$wp_handle_reg_duplicates = (bool) $handler->restrict_duplicates() ? 'checked' : '';
$wpreg_phone_type         = $handler->get_phone_html_tag();
$wpreg_email_type         = $handler->get_email_html_tag();
$wpreg_both_type          = $handler->get_both_html_tag();
$form_name                = $handler->get_form_name();
$auto_activate_users      = $handler->disable_auto_activation() ? '' : 'checked';

require_once MOV_DIR . 'views/forms/defaultwordpressregistrationform.php';
get_plugin_form_link( $handler->get_form_documents() );
