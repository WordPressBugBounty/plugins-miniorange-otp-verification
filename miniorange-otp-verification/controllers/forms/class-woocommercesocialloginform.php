<?php
/**
 * Load admin view for WooCommerceSocialLoginForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WooCommerceSocialLoginForm;

$handler         = WooCommerceSocialLoginForm::instance();
$wc_social_login = (bool) $handler->is_form_enabled() ? 'checked' : '';
$form_name       = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/woocommercesocialloginform.php';
get_plugin_form_link( $handler->get_form_documents() );
