<?php
/**
 * Load admin view for Ultimate Member Profile form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoMessages;

echo '	
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="um_pass_reset_basic" class="app_enable" data-toggle="um_pass_reset_options" 
                name="mo_customer_validation_um_pass_reset_enable" value="1" ' . esc_attr( $is_um_pass_reset_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
           
            <div class="mo_registration_help_desc" id="um_pass_reset_options">

                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="um_pass_reset_form_phone" class="app_enable" 
                    data-toggle="um_pass_reset_phone_option" name="mo_customer_validation_um_pass_reset_enable_type" 
                    value="' . esc_attr( $um_pass_reset_phone_type ) . '" ' . ( $um_pass_reset_enable_type === $um_pass_reset_phone_type ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>

           <div id="umpassreset_phone_instructions" 
					     class="mo_registration_help_desc"
                        id="umpassreset_phone_instructions">
                    ' . esc_html( mo_( 'Follow the following steps to add a users phone number in the database' ) ) . ':
                    <ol>
                    <li>' . esc_html( mo_( 'Enter the phone User Meta Key.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
										<input class=" mo-form-input" id="mo_customer_validation_um_pass_reset_field_key" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $um_pass_reset_phone_field_key ) . '" type="text" name="mo_customer_validation_um_pass_reset_field_key" >
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
                    <div>
					    <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
							        name="mo_customer_validation_um_pass_reset_only_phone" 
							        value="1"' . esc_attr( $um_pass_reset_only_phone_reset ) . '/>
                                    <strong>' . esc_html( mo_( 'Use only Phone Number. Do not allow username or email to reset password.' ) ) . ' </strong>
                    </div>
            </div>

         <div>
            <input type="radio" ' . esc_attr( $disabled ) . ' id="um_pass_reset_form_email" class="app_enable" 
            data-toggle="um_pass_reset_email_option" name="mo_customer_validation_um_pass_reset_enable_type" 
            value="' . esc_attr( $um_pass_reset_email_type ) . '" ' . ( ( $um_pass_reset_enable_type ) === ( $um_pass_reset_email_type ) ? 'checked' : '' ) . ' />
            <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
        </div>

        <div>
					<div class="pt-mo-4">
						<div class="mo-input-wrapper">
							<label class="mo-input-label">' . esc_html( mo_( 'Verification Button text' ) ) . '</label>
							<input class=" mo-form-input" 
								placeholder="Enter the verification button text" 
								value="' . esc_attr( $um_resetpass_button_text ) . '" 
								type="text" name="mo_customer_validation_um_pr_button_text" >
						</div>
					</div>					
				</div>

       </div>
       </div>';


