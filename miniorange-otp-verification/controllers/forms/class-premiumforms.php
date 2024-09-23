<?php
/**
 * Loads admin view for PremiumForms.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\PremiumForms;
use OTP\Helper\PremiumFeatureList;
use OTP\Objects\Tabs;

$premium_forms = PremiumFeatureList::instance();
$premium_forms = $premium_forms->get_premium_forms();

$handler     = PremiumForms::instance();
$form_name   = $handler->get_form_name();
$plan_name   = isset( $_GET['form_name']['plan_name'] ) ? sanitize_text_field( wp_unslash( $_GET['form_name']['plan_name'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the URL for checking the form name, doesn't require nonce verification.
$license_url = add_query_arg( array( 'page' => $tab_details->tab_details[ Tabs::PRICING ]->menu_slug ), $request_uri );

get_plugin_form_link( $handler->get_form_documents() );
require_once MOV_DIR . 'views/forms/premiumforms.php';

