<?php
/**
 * Load admin view for WPLoginForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WPLoginForm;
use OTP\Helper\MoUtility;

$handler                     = WPLoginForm::instance();
$wp_login_enabled            = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wp_login_hidden             = 'checked' === $wp_login_enabled ? '' : 'style=display:none';
$wp_login_enabled_type       = (bool) $handler->savePhoneNumbers() ? 'checked' : '';
$wp_login_field_key          = $handler->get_phone_key_details();
$wp_login_admin              = (bool) $handler->byPassCheckForAdmins() ? 'checked' : '';
$wp_login_with_phone         = (bool) $handler->allowLoginThroughPhone() ? 'checked' : '';
$wp_handle_duplicates        = (bool) $handler->restrict_duplicates() ? 'checked' : '';
$wp_enabled_type             = $handler->get_otp_type_enabled();
$wp_phone_type               = $handler->get_phone_html_tag();
$wp_email_type               = $handler->get_email_html_tag();
$form_name                   = $handler->get_form_name();
$skip_pass                   = $handler->getSkipPasswordCheck() ? 'checked' : '';
$skip_pass_fallback_div      = $handler->getSkipPasswordCheck() ? 'block' : 'hidden';
$skip_pass_fallback          = $handler->getSkipPasswordCheckFallback() ? 'checked' : '';
$user_field_text             = $handler->getUserLabel();
$otpd_enabled                = $handler->isDelayOtp() ? 'checked' : '';
$otpd_enabled_div            = $handler->isDelayOtp() ? 'block' : 'hidden';
$otpd_time_interval          = $handler->getDelayOtpInterval();
$redirect_page               = $handler->redirectToPage();
$redirection_after_login     = $handler->select_redirection_after_login();
$login_with_otp_button_text  = $handler->get_login_with_otp_button_text();
$login_with_pass_button_text = $handler->get_login_with_pass_button_text();
$login_with_pass_button_css  = $handler->get_login_with_pass_button_css();
$wp_default_redirect         = 'redirect_to_default';
$wp_redirect_to_page         = 'redirect_to_the_page';
$redirect_page_id            = MoUtility::is_blank( $redirect_page ) ? '' : get_posts(
	array(
		'title'     => $redirect_page,
		'post_type' => 'page',
	)
)[0]->ID;

require_once MOV_DIR . 'views/forms/wploginform.php';
get_plugin_form_link( $handler->get_form_documents() );
