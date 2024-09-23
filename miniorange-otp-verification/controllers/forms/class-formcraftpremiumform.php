<?php
/**
 * Load admin view for FormCraftPremiumForm.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\FormCraftPremiumForm;

$handler                = FormCraftPremiumForm::instance();
$fcpremium_enabled      = $handler->is_form_enabled() ? 'checked' : '';
$fcpremium_hidden       = 'checked' === $fcpremium_enabled ? '' : 'hidden';
$fcpremium_enabled_type = $handler->get_otp_type_enabled();
$fcpremium_list         = admin_url() . 'admin.php?page=formcraft-dashboard';
$fcpremium_otp_enabled  = $handler->get_form_details();
$fcpremium_type_phone   = $handler->get_phone_html_tag();
$fcpremium_type_email   = $handler->get_email_html_tag();
$form_name              = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/formcraftpremiumform.php';
get_plugin_form_link( $handler->get_form_documents() );
