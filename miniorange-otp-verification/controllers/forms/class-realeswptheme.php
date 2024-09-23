<?php
/**
 * Load admin view for RealesWPTheme.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\RealesWPTheme;

$handler            = RealesWPTheme::instance();
$reales_enabled     = $handler->is_form_enabled() ? 'checked' : '';
$reales_hidden      = 'checked' === $reales_enabled ? '' : 'hidden';
$reales_enable_type = $handler->get_otp_type_enabled();
$reales_type_phone  = $handler->get_phone_html_tag();
$reales_type_email  = $handler->get_email_html_tag();
$form_name          = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/realeswptheme.php';
get_plugin_form_link( $handler->get_form_documents() );
