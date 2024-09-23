<?php
/**
 * Load admin view for WPLoginForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoMessages;

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
			<input  type="checkbox" ' . esc_attr( $disabled ) . '
					id="wp_login"
					class="app_enable"
					data-toggle="wp_login_options"
					name="mo_customer_validation_wp_login_enable"
					value="1"
					' . esc_attr( $wp_login_enabled ) . ' />
			<strong>' . esc_html( $form_name ) . '</strong>';

echo '	    <div class="mo_registration_help_desc" id="wp_login_options">
	
				 <div>
					<input  type="radio" ' . esc_attr( $disabled ) . '
							id="wp_form_phone"
							class="app_enable"
							data-toggle="wp_phone_option"
							name="mo_customer_validation_wp_login_enable_type"
							value="' . esc_attr( $wp_phone_type ) . '"
							' . ( esc_attr( $wp_enabled_type ) === esc_attr( $wp_phone_type ) ? 'checked' : '' ) . ' />
					<b>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</b>
				 </div>
				 <div   ' . ( esc_attr( $wp_enabled_type ) !== esc_attr( $wp_phone_type ) ? 'style=display:none' : '' ) . '
						class="mo_registration_help_desc_internal"
						id="wp_phone_option" ' . esc_attr( $disabled ) . '">
					' . esc_html( mo_( 'Follow the following steps to add a users phone number in the database' ) ) . ':
					<ol>
						<li>' . esc_html( mo_( 'Enter the phone User Meta Key.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
										<input class=" mo-form-input" id="mo_customer_validation_wp_login_phone_field_key" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $wp_login_field_key ) . '" type="text" name="mo_customer_validation_wp_login_phone_field_key" >
									</div>
								</div>
								<div>';

								mo_draw_tooltip(
									MoMessages::showMessage( MoMessages::META_KEY_HEADER ),
									MoMessages::showMessage( MoMessages::META_KEY_BODY )
								);
								echo '
								</div>
							</div>
							<div class="mo_otp_note" style="margin-top:1%;">' . esc_attr( mo_( "If you don't know the metaKey against which the phone number is stored for all your users then put the default value as phone." ) ) . ' </div>
						</li>                            
						<li>' . esc_html( mo_( 'Click on the Save Button to save your settings.' ) ) . '</li>
					</ol>

					<div class="mo_registration_help_desc">
							<div>
								<input  type="checkbox" ' . esc_attr( $disabled ) . ' id="wp_login_reg" 
										name="mo_customer_validation_wp_login_register_phone" value="1"
									' . esc_attr( $wp_login_enabled_type ) . ' />' .
										'<strong>' .
										esc_html( mo_( 'Allow the user to add a phone number if it does not exist.' ) ) .
										'</strong>
							</div>
							<div>
								<input  type="checkbox" ' . esc_attr( $disabled ) . '
										id="wp_login_admin"
										name="mo_customer_validation_wp_login_allow_phone_login"
										value="1"
										class="app_enable"
										data-toggle="wp_change_labels"
										' . esc_attr( $wp_login_with_phone ) . ' /><strong>' .
										esc_html( mo_( 'Allow users to login with their phone number.' ) ) . '</strong>
								<div    ' . ( ! $wp_login_with_phone ? 'style=display:none' : '' ) . '
										id="wp_change_labels"
										class="mo_registration_help_desc_internal">
										<div class="p-mo-4">
											<div class="mo-input-wrapper">
												<label class="mo-input-label">' . esc_html( mo_( 'Username Field text' ) ) . '</label>
												<input class=" mo-form-input" placeholder="Enter the Username Field text" value="' . esc_attr( $user_field_text ) . '" type="text" name="mo_customer_validation_wp_username_label_text" >
											</div>
										</div>
								</div>
							</div>
							<div>
								<input  type="checkbox" ' . esc_attr( $disabled ) . '
										id="wp_login_admin"
										name="mo_customer_validation_wp_login_restrict_duplicates"
										value="1"
										' . esc_attr( $wp_handle_duplicates ) . ' />
								<strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>
							</div>
						</div>
					</div>

					<div>
						<input  type="radio" ' . esc_attr( $disabled ) . '
								id="wp_form_email"
								class="app_enable"
								data-toggle="wp_email_option"
								name="mo_customer_validation_wp_login_enable_type"
								value="' . esc_attr( $wp_email_type ) . '"
								' . ( esc_attr( $wp_enabled_type ) === esc_attr( $wp_email_type ) ? 'checked' : '' ) . ' />
						<b>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</b>
					</div>
						<div   ' . ( esc_attr( $wp_enabled_type ) !== esc_attr( $wp_email_type ) ? 'style=display:none' : '' ) . '
							class="mo_registration_help_desc_internal"
							id="wp_email_option" ' . esc_attr( $disabled ) . '">
							<ol>
							<li>' . esc_html( mo_( 'Click on the Save Settings Button to save your settings and enable Email verification on login form.' ) ) . '</li>
							</ol>
						</div>	
					
					
						<b>' . esc_html( mo_( 'Advance Settings for Phone & Email verification:' ) ) . '</b>
					<div class= "flex" style="border-radius: 5px;padding: 6px;border: 1px groove; background-color: #FCFCFB;margin-left:10px;">
						<div>
							<input  type="radio" ' . esc_attr( $disabled ) . '
									id="login_with_pass_and_otp"
									class="app_enable"
									name="mo_customer_validation_wp_login_skip_password"
									value="" ' . ( esc_attr( $skip_pass ) === '' ? 'checked' : '' ) . '/>
							<strong>' . esc_html( mo_( 'Login with Password and OTP ( 2FA ) ' ) ) . '</strong>
						</div>

						<div  style=" margin-left: 40px">
							<input  type="radio" ' . esc_attr( $disabled ) . '
									id="otp_skip_pass"
									class="app_enable"
									name="mo_customer_validation_wp_login_skip_password"
									value="1" ' . esc_attr( $skip_pass ) . ' />
									<strong>' . esc_html( mo_( 'Login with only OTP' ) ) . '</strong>

							<div style="margin-top:10px; margin-left:20px" class="mo_registration_help_desc_internal" ' . esc_attr( $skip_pass_fallback_div ) . ' id="otp_skip_pass_fallback_div">
								<input  type="checkbox" ' . esc_attr( $disabled ) . '
									id="otp_skip_pass_fallback"
									name="mo_customer_validation_wp_login_skip_password_fallback"
									value="1" ' . esc_attr( $skip_pass_fallback ) . ' />
									<strong>' . esc_html( mo_( 'Allow users to login with Username and Password ' ) ) . '</strong>
							</div>
						</div>
		   			</div>
				
					<div style="margin-left:10px;">
						<input  type="checkbox" ' . esc_attr( $disabled ) . '
								id="wp_login_admin"
								name="mo_customer_validation_wp_login_bypass_admin"
								value="1"
								' . esc_attr( $wp_login_admin ) . ' />
						<b>' . esc_html( mo_( 'Allow the administrator to bypass OTP Verification during login.' ) ) . '</b>
					</div>
				
					<div style="margin-left:10px;">
						<input  type="checkbox" ' . esc_attr( $disabled ) . '
								id="wp_login_delay_otp"
								class="app_enable"
								data-toggle="otp_delay_time_interval"
								name="mo_customer_validation_wp_login_delay_otp"
								value="1" ' . esc_attr( $otpd_enabled ) . ' />
						<b>' . esc_html( mo_( 'Delay OTP Verification' ) ) . '</b>
						<div class="mo_registration_help_desc_internal" ' . esc_attr( $otpd_enabled_div ) . ' id="otp_delay_time_interval">
								<div class="flex gap-mo-1">
									<i>' . esc_html( mo_( 'Enter the interval after which you wish for OTP Verification to get invoked for the user' ) ) . ' : </i>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Time in mins' ) ) . '</label>
										<input class=" mo-form-input" id="mo_customer_validation_wp_login_phone_field_key" placeholder="Enter the time in mins" value="' . esc_attr( $otpd_time_interval ) . '" type="text" name="mo_customer_validation_wp_login_delay_otp_interval" >
									</div>
								</div>
						</div>
					</div>
					<div>
						<b>' . esc_html( mo_( 'Select Redirection after login' ) ) . ': </b>
					
						<div class= "flex" style="border-radius: 5px;padding: 6px;border: 1px groove; background-color: #FCFCFB;margin-left:10px;">
							<div>
								<input  type="radio" ' . esc_attr( $disabled ) . '
										id="mo_redirect_to_default"
										class="app_enable"
										name="mo_customer_validation_wp_login_redirection_enable"
										value="' . esc_attr( $wp_default_redirect ) . '" ' . ( esc_attr( $redirection_after_login ) !== esc_attr( $wp_redirect_to_page ) ? 'checked' : '' ) . '/>
								<strong>' . esc_html( mo_( 'Redirect to Default Page (Current Page)' ) ) . '</strong>
							</div>

							<div  style="margin-left: 50px;">
								<input  type="radio" ' . esc_attr( $disabled ) . '
										id="mo_custom_redirect"
										class="app_enable"
										name="mo_customer_validation_wp_login_redirection_enable"
										value="' . esc_attr( $wp_redirect_to_page ) . '" ' . ( esc_attr( $redirection_after_login ) === esc_attr( $wp_redirect_to_page ) ? 'checked' : '' ) . ' />
									<strong>' . esc_html( mo_( 'Select Redirection Page:  ' ) ) . '</strong>';
									wp_dropdown_pages(
										array(
											'name'     => 'mo_login_page_id',
											'selected' => esc_attr( $redirect_page_id ),
										)
									);
									echo ' 
							</div>
		   				</div>
					</div>
					<div class="flex py-mo-4 gap-mo-8">
						<div class="mo-input-wrapper">
							<label class="mo-input-label">' . esc_html( mo_( ' Login With OTP Button text' ) ) . '</label>
							<input class=" mo-form-input w-full" 
								placeholder="Enter the login with OTP button text" 
								value="' . esc_attr( $login_with_otp_button_text ) . '" 
								type="text" name="mo_customer_validation_wp_login_with_otp_button_text" >
						</div>
						<div class="mo-input-wrapper">
							<label class="mo-input-label">' . esc_html( mo_( 'Login With Password Button text' ) ) . '</label>
							<input class=" mo-form-input w-full" 
								placeholder="Enter the login with password button text" 
								value="' . esc_attr( $login_with_pass_button_text ) . '" 
								type="text" name="mo_customer_validation_wp_login_with_pass_button_text" >
						</div>
					</div>
					<div class="mo-input-wrapper w-full">
							<label class="mo-input-label">' . esc_html( mo_( 'Login With Password Button CSS' ) ) . '</label>
							<input class=" mo-form-input w-full" 
								placeholder="Enter the login with password button CSS with semicolons eg:- width:50%; color: black;" 
								value="' . esc_attr( $login_with_pass_button_css ) . '" 
								type="text" name="mo_customer_validation_wp_login_with_pass_button_css" >
					</div>
				</div>
		 	</div>';


