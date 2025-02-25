<?php
/**
 * Load admin view for WooCommerceProductVendorsForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
	        <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
	                id="wc_pv_default" 
	                data-toggle="wc_pv_default_options" 
	                class="app_enable" 
	                name="mo_customer_validation_wc_pv_default_enable" 
	                value="1"
		            ' . esc_attr( $wc_pv_registration ) . ' />
            <strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="wc_pv_default_options">
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="wc_pv_phone" 
					        class="app_enable" 
					        data-toggle="wc_pv_phone_options" 
					        name="mo_customer_validation_wc_pv_enable_type" 
					        value="' . esc_attr( $wc_pv_reg_type_phone ) . '"
						    ' . ( esc_attr( $wc_pv_enable_type ) === esc_attr( $wc_pv_reg_type_phone ) ? 'checked' : '' ) . '/>
				    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				<div ' . ( esc_attr( $wc_pv_enable_type ) !== esc_attr( $wc_pv_reg_type_phone ) ? 'hidden' : '' ) . ' 
				        class="mo_registration_help_desc_internal" 
						id="wc_pv_phone_options" >
						<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
						        name="mo_customer_validation_wc_pv_restrict_duplicates" value="1"
								' . esc_attr( $wc_pv_restrict_duplicates ) . ' />
                        <strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>
				</div>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="wc_pv_email" 
					        class="app_enable" 
					        name="mo_customer_validation_wc_pv_enable_type" 
					        value="' . esc_attr( $wc_pv_reg_type_email ) . '"
						    ' . ( esc_attr( $wc_pv_enable_type ) === esc_attr( $wc_pv_reg_type_email ) ? 'checked' : '' ) . '/>
					<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
