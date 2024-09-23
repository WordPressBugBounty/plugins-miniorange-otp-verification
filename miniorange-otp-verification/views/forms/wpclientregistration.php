<?php
/**
 * Load admin view for WPClientRegistrationForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;

echo '		<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
		        <input  type="checkbox" 
		                ' . esc_attr( $disabled ) . ' 
		                id="wp_client" 
		                class="app_enable" 
		                data-toggle="wp_client_options" 
		                name="mo_customer_validation_wp_client_enable" value="1"
		                ' . esc_attr( $wp_client_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
                <div class="mo_registration_help_desc" id="wp_client_options">
					
					<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
					<div>
					    <input  type="radio" 
					            ' . esc_attr( $disabled ) . ' 
					            data-toggle="wp_client_phone_instructions" 
					            id="wp_client_phone" 
					            class="form_options app_enable" 
						        name="mo_customer_validation_wp_client_enable_type" 
						        value="' . esc_attr( $wp_client_type_phone ) . '"
							    ' . ( esc_attr( $wp_client_enable_type ) === esc_attr( $wp_client_type_phone ) ? 'checked' : '' ) . ' />
                        <strong>' . esc_html( mo_( 'Enable Phone verification' ) ) . '</strong>
						
						<div    ' . ( esc_attr( $wp_client_enable_type ) !== esc_attr( $wp_client_type_phone ) ? 'hidden' : '' ) . ' 
						        id="wp_client_phone_instructions" 
						        class="mo_registration_help_desc_internal">
                                <input  type="checkbox" 
                                        ' . esc_attr( $disabled ) . ' 
                                        id="mo_customer_validation_wp_client_restrict_duplicates" 
                                        name="mo_customer_validation_wp_client_restrict_duplicates" 
                                        value="1"
                                        ' . esc_attr( $restrict_duplicates ) . '/>
                                <strong>' . esc_html( mo_( 'Restrict Duplicate phone number to sign up.' ) ) . '</strong>
						</div>
					</div>
					<div>
					    <input  type="radio" 
					            ' . esc_attr( $disabled ) . ' 
					            id="wp_client_email" 
					            class="form_options app_enable" 
						        name="mo_customer_validation_wp_client_enable_type" 
						        value="' . esc_attr( $wp_client_type_email ) . '"
						        ' . ( esc_attr( $wp_client_enable_type ) === esc_attr( $wp_client_type_email ) ? 'checked' : '' ) . ' />
						<strong>' . esc_html( mo_( 'Enable Email verification' ) ) . '</strong>
					</div>
				</div>
			</div>';
