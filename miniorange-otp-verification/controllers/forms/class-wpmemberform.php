<?php
/**
 * Load admin view for WpMemberForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WpMemberForm;

$handler               = WpMemberForm::instance();
$wp_member_reg_enabled = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wp_member_reg_hidden  = 'checked' === $wp_member_reg_enabled ? '' : 'hidden';
$wpmember_enabled_type = $handler->get_otp_type_enabled();
$wpm_field_list        = admin_url() . 'admin.php?page=wpmem-settings&tab=fields';
$wpm_type_phone        = $handler->get_phone_html_tag();
$wpm_type_email        = $handler->get_email_html_tag();
$form_name             = $handler->get_form_name();
$wpmember_field_key    = $handler->get_phone_key_details();

require_once MOV_DIR . 'views/forms/wpmemberform.php';
get_plugin_form_link( $handler->get_form_documents() );
