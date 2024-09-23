<?php
/**
 * Load admin view for ElementorProFormFree.
 *
 * @package miniorange-otp-verification/controller/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\GatewayFunctions;

$gateway = GatewayFunctions::instance();
$gateway->show_configuration_page( $disabled );
