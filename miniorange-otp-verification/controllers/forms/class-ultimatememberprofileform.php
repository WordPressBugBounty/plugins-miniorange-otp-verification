<?php
/**
 * Load admin view for UltimateMemberProfileForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\UltimateMemberProfileForm;

$handler                    = UltimateMemberProfileForm::instance();
$um_acc_enabled             = $handler->is_form_enabled() ? 'checked' : '';
$um_acc_hidden              = 'checked' === $um_acc_enabled ? '' : 'hidden';
$um_acc_enabled_type        = $handler->get_otp_type_enabled();
$um_profile_field_key       = $handler->get_phone_key_details();
$um_acc_forms               = admin_url() . 'edit.php?post_type=um_form';
$um_acc_type_phone          = $handler->get_phone_html_tag();
$um_acc_type_email          = $handler->get_email_html_tag();
$um_acc_type_both           = $handler->get_both_html_tag();
$um_acc_restrict_duplicates = $handler->restrict_duplicates() ? 'checked' : '';
$form_name                  = $handler->get_form_name();
$um_acc_button_text         = $handler->get_button_text();

require_once MOV_DIR . 'views/forms/ultimatememberprofileform.php';
get_plugin_form_link( $handler->get_form_documents() );
