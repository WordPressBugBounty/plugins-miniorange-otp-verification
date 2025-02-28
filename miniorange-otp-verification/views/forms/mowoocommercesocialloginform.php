<?php
/**
 * Load admin view for WooCommerceSocialLoginForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo ' 	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
 	        <input type="checkbox" ' . esc_attr( $disabled ) . ' 
                id="wc_social" 
                class="app_enable" 
                name="mo_customer_validation_wc_social_login_enable" 
                value="1"
			    ' . esc_attr( $wc_social_login ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo ' </div>';

