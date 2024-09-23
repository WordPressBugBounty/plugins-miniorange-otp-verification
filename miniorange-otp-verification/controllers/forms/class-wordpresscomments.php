<?php
/**
 * Load admin view for WordPressComments.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Handler\Forms\WordPressComments;

$handler               = WordPressComments::instance();
$wpcomment_enabled     = (bool) $handler->is_form_enabled() ? 'checked' : '';
$wpcomment_hidden      = 'checked' === $wpcomment_enabled ? '' : 'hidden';
$wpcomment_type        = $handler->get_otp_type_enabled();
$wpcomment_skip_verify = $handler->bypass_for_logged_in_users() ? 'checked' : '';
$wpcomment_type_phone  = $handler->get_phone_html_tag();
$wpcomment_type_email  = $handler->get_email_html_tag();
$form_name             = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/wordpresscomments.php';
get_plugin_form_link( $handler->get_form_documents() );
