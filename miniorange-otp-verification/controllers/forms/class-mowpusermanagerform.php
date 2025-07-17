<?php
/**
 * Load admin view for WP User Manager Form
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Handler\Forms\MoWPUserManagerForm;

$handler                      = MoWPUserManagerForm::instance();
$is_wp_user_manager_enabled   = (bool) $handler->is_form_enabled() ? 'checked' : '';
$is_wp_user_manager_hidden    = 'checked' === $is_wp_user_manager_enabled ? '' : 'hidden';
$wp_user_manager_enabled_type = $handler->get_otp_type_enabled();
$wp_user_manager_form_list    = admin_url() . 'admin.php?page=wpum-registration-forms#/';
$wp_user_manager_email_type   = $handler->get_email_html_tag();
$form_name                    = $handler->get_form_name();

require_once MOV_DIR . 'views/forms/mowpusermanagerform.php';
get_plugin_form_link( $handler->get_form_documents() );
