<?php
/**
 * Load admin view for Buddy Press Registyration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

echo '
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="caldera_basic" class="app_enable" data-toggle="caldera_options"
                name="mo_customer_validation_caldera_enable" value="1" ' . esc_attr( $is_caldera_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
            <div class="mo_registration_help_desc" id="caldera_options">
                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="caldera_form_email" class="app_enable"
                    data-toggle="caldera_email_option" name="mo_customer_validation_caldera_enable_type" 
                    value="' . esc_attr( $caldera_email_type ) . '" ' . ( esc_attr( $caldera_enabled_type ) === esc_attr( $caldera_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( esc_attr( $caldera_enabled_type ) !== esc_attr( $caldera_email_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="caldera_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $caldera_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a>
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your caldera Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Note the Form ID from the Form Settings Page.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an Email Field to your form. Note the Field ID of the Email field.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add a Verification Field to your form where users will enter the OTP sent to their Email Address. Note the Field ID of the Verification field.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Make sure Both Email Field and Verification Field are required Fields.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Email Field ID and Verification Field ID below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . '
                            onclick="add_caldera(\'email\',1);" class="mo-form-button secondary" />&nbsp;

                            <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_caldera(1);" class="mo-form-button secondary" />
                            <br/><br/>';

						$form_results = get_multiple_form_select( $caldera_list_of_forms_otp_enabled, true, true, $disabled, 1, 'caldera', 'ID' );
						$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '              </ol>
                </div>


                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="caldera_form_phone"
                        class="app_enable" data-toggle="caldera_phone_option" name="mo_customer_validation_caldera_enable_type" 
                        value="' . esc_attr( $caldera_phone_type ) . '"' . ( esc_attr( $caldera_enabled_type ) === esc_attr( $caldera_phone_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>
                    
                <div ' . ( esc_attr( $caldera_enabled_type ) !== esc_attr( $caldera_phone_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal"
                    id="caldera_phone_option" ' . esc_attr( $disabled ) . '">
                    <ol>
                        <li><a href="' . esc_url( $caldera_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a>
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your caldera Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Note the Form ID from the Form Settings Page.' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add a <b>Phone Number</b> field to your form. Note the Field ID of the Phone field.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html(
							mo_( 'Add a Verification Field to your form where users will enter the OTP sent to their Phone. Note the Field ID of the Verification field.' )
						) . '</li>
                        <li>' . esc_html( mo_( 'Make sure Both Phone Field and Verification Field are required Fields.' ) )
						. '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Phone Field ID and Verification Field ID below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_caldera(\'phone\',2);
                                " class="mo-form-button secondary" />&nbsp; <input type="button" value="-" ' . esc_attr( $disabled ) . '
                                onclick="remove_caldera(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $caldera_list_of_forms_otp_enabled, true, true, $disabled, 2, 'caldera', 'ID' );
								$counter2     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '</ol>
                    </div>  
                    <div style="margin-left:2%;">
                        <div class="pt-mo-4">
                            <div class="mo-input-wrapper">
                                <label class="mo-input-label">' . esc_html( mo_( 'Verification Field Text' ) ) . '</label>
                                <input class=" mo-form-input" 
                                    placeholder="Enter the verification button text" 
                                    value="' . esc_attr( $button_text ) . '" 
                                    type="text" name="mo_customer_validation_caldera_button_text" >
                            </div>
						</div>
                        </div>             
                </div>
        </div>';

		multiple_from_select_script_generator( true, true, 'caldera', 'ID', array( $counter1, $counter2, 0 ) );

