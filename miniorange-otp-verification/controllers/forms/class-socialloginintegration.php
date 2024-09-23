<?php
/**
 * Load admin view for SocialLoginIntegration.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\SocialLoginIntegration;

$handler                 = SocialLoginIntegration::instance();
$mo_social_login_enabled = (bool) $handler->is_form_enabled() ? 'checked' : '';
$mo_social_login_hidden  = 'checked' === $mo_social_login_enabled ? '' : 'hidden';
$form_name               = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/socialloginintegration.php';
get_plugin_form_link( $handler->get_form_documents() );
