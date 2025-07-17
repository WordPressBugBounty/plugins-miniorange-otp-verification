<?php
/**
 * Loads admin view for Ultimate memeber password Forms.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\MoUMPasswordReset;

$handler                        = MoUMPasswordReset::instance();
$is_um_pass_reset_enabled       = $handler->is_form_enabled() ? 'checked' : '';
$is_um_pass_reset_hidden        = 'checked' === $is_um_pass_reset_enabled ? '' : 'style=display:none';
$um_pass_reset_enable_type      = $handler->get_otp_type_enabled();
$um_pass_reset_email_type       = $handler->get_email_html_tag();
$um_pass_reset_phone_type       = $handler->get_phone_html_tag();
$um_pass_reset_phone_field_key  = $handler->get_phone_key_details();
$um_pass_reset_only_phone_reset = $handler->getIsOnlyPhoneReset() ? 'checked' : '';
$form_name                      = $handler->get_form_name();
$um_resetpass_button_text       = $handler->get_button_text();

require_once MOV_DIR . 'views/forms/moumpasswordreset.php';
get_plugin_form_link( $handler->get_form_documents() );
