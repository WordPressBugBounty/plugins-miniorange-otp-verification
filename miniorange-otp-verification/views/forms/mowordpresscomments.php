<?php
/**
 * Load admin view for WordPressCommentsForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
	        <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
	                id="wpcomment" 
	                class="app_enable" 
	                data-toggle="wpcomment_options" 
	                name="mo_customer_validation_wpcomment_enable" 
	                value="1"
			        ' . esc_attr( $wpcomment_enabled ) . ' />
            <strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="wpcomment_options">
				<div>
					<input  type="checkbox" 
					        class="form_options" ' . esc_attr( $wpcomment_skip_verify ) . ' 
					        id="mo_customer_validation_wpcomment_enable_for_loggedin_users" 
					        name="mo_customer_validation_wpcomment_enable_for_loggedin_users" 
					        value="1"> 
                    <strong>' . esc_html( mo_( 'Skip OTP Verification for Logged In users.' ) ) . '</strong><br>
                    <i>( ' . esc_html( mo_( 'Enabling this feature, logged in users are not required to verify.' ) ) . ')</i>
				</div>
				
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="wpcomment_phone" 
					        class="app_enable" 
					        name="mo_customer_validation_wpcomment_enable_type" 
					        value="' . esc_attr( $wpcomment_type_phone ) . '"
						    ' . esc_attr( ( $wpcomment_type === $wpcomment_type_phone ? 'checked' : '' ) ) . '/>
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="wpcomment_email" 
					        class="app_enable" 
					        name="mo_customer_validation_wpcomment_enable_type" 
					        value="' . esc_attr( $wpcomment_type_email ) . '"
						    ' . esc_attr( ( $wpcomment_type === $wpcomment_type_email ? 'checked' : '' ) ) . '/>
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
