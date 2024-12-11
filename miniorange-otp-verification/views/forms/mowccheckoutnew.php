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

				<div>
					<input  type="checkbox"
						' . esc_attr( $disabled ) . '
						' . esc_attr( $wc_new_guest_checkout ) . '
						class="app_enable"
						name="mo_customer_validation_wc_new_checkout_guest"
						value="1" />
					<b>' . esc_html( mo_( 'Enable Verification only for Guest Users.' ) ) . '</b>';
								mo_draw_tooltip(
									MoMessages::showMessage( MoMessages::WC_GUEST_CHECKOUT_HEAD ),
									MoMessages::showMessage( MoMessages::WC_GUEST_CHECKOUT_BODY )
								);
								echo '
					<br/>
				</div>

				<div>
					<input  type="checkbox"
							' . esc_attr( $disabled ) . '
							' . esc_attr( $wc_new_checkout_popup ) . '
							class="app_enable"
							name="mo_customer_validation_wc_new_checkout_popup"
							value="1"
							type="checkbox" />
					<b>' . esc_html( mo_( 'Show a popup for validating OTP.' ) ) . '</b>
					<br/>
				</div>

				<div>
					<input  type="checkbox"
							' . esc_attr( $disabled ) . '
							' . esc_attr( $wc_new_checkout_selection ) . '
							class="app_enable"
							data-toggle="wc_new_selective_payment"
							name="mo_customer_validation_wc_checkout_new_selective_payment"
							value="1"
							type="checkbox" />
					<b>' . esc_html( mo_( 'Validate OTP for selective Payment Methods.' ) ) . '</b>
					<br/>
				</div>
				<div id="wc_new_selective_payment" class="mo_registration_help_desc_internal"
					' . esc_attr( $wc_new_checkout_selection_hidden ) . ' style="padding-left:3%;">
						<b>
							<label for="wc_payment" style="vertical-align:top;">' .
									esc_html( mo_( 'Select Payment Methods (Hold Ctrl Key to Select multiple):' ) ) . '
							</label>
						</b>';

								get_wc_payment_dropdown( $disabled, $checkout_payment_plans );
								echo '
				</div>
			</div>
		</div>';
