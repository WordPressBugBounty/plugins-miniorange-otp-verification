<?php
/**
 * Loads list of addons.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	$moaddon_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore -- false positive.
	$woocommerce_url     = add_query_arg( array( 'addon' => 'woocommerce_notif' ), $moaddon_request_uri );
	$custom              = add_query_arg( array( 'addon' => 'custom' ), $moaddon_request_uri );
	$ultimate_mem        = add_query_arg( array( 'addon' => 'um_notif' ), $moaddon_request_uri );
	$ultimate_mem_pr     = add_query_arg( array( 'addon' => 'umpr_notif' ), $moaddon_request_uri );
	$woocommerce_pr      = add_query_arg( array( 'addon' => 'wcpr_notif' ), $moaddon_request_uri );


if ( isset( $_GET['addon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- Reading GET parameter from the addon for checking the addon name, doesn't require nonce verification.
	return;
}

require_once MOV_DIR . 'views/add-on.php';
