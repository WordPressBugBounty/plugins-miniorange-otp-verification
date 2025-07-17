<?php
/**
 * Loads contact us form.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$nonce = $admin_handler->get_nonce_value();
$email = get_mo_option( 'admin_email' );
$phone = get_mo_option( 'admin_phone' );
$phone = $phone ? $phone : '';


require_once MOV_DIR . 'views/contactus.php';
