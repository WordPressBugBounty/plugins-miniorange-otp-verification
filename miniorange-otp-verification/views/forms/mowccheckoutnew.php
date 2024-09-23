<?php
/**
 * Load admin view for WooCommerceCheckoutForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoMessages;

echo ' 	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
 			<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
 					id="wc_new_checkout" 
 					data-toggle="wc_new_checkout_options" 
 					class="app_enable" 
 					name="mo_customer_validation_wc_new_checkout_enable" 
 					value="1" 
 					' . esc_attr( $wc_new_checkout ) . ' />
			<strong>' . esc_html( $form_name ) . '</strong>';

if ( class_exists( Automattic\WooCommerce\Internal\Utilities\BlocksUtil::class ) ) {
	if ( ! ( WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ) ) {
		echo '	<div style="border: none;background-color: #ffdede; color: #fc6060;" class="notice mo_sms_notice font-normal rounded-md py-mo-3">
					It seems you are using Classic Checkout form. Please choose <b>WooCommerce Checkout Form - Classic Form</b> from the list.
					<i><a target="_blank" href="https://plugins.miniorange.com/block-or-classic-wc-checkout-form-otp">[Know more about WooCommerce checkout form]</a></i>
				</div>';
	}
}

echo '		<div class="mo_registration_help_desc" id="wc_new_checkout_options">
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				<div>
				    <input  type="radio" ' . esc_attr( $disabled ) . ' 
				            id="wc_new_checkout_phone" 
				            class="app_enable" 
				            data-toggle="wc_new_checkout_phone_options"
				            name="mo_customer_validation_wc_new_checkout_type" 
				            value="' . esc_attr( $wc_new_type_phone ) . '"
						    ' . ( esc_attr( $wc_new_checkout_enable_type ) === esc_attr( $wc_new_type_phone ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				<div>
				    <input  type="radio" ' . esc_attr( $disabled ) . ' 
				            id="wc_new_checkout_email" 
				            class="app_enable" 
				            name="mo_customer_validation_wc_new_checkout_type" 
				            value="' . esc_attr( $wc_new_type_email ) . '"
						    ' . ( esc_attr( $wc_new_checkout_enable_type ) === esc_attr( $wc_new_type_email ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
