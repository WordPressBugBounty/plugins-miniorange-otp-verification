<?php
/**
 * Load admin view for WPForms.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

echo '
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="wpform_basic" class="app_enable" data-toggle="wpform_options"
                name="mo_customer_validation_wpform_enable" value="1" ' . esc_attr( $is_wpform_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>';

echo '<div class="mo_registration_help_desc" id="wpform_options">
                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="wp_form_email" class="app_enable"
                    data-toggle="wpform_email_option" name="mo_customer_validation_wpform_enable_type" 
                    value="' . esc_attr( $wpform_email_type ) . '" ' . ( esc_attr( $wpform_enabled_type ) === esc_attr( $wpform_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( esc_attr( $wpform_enabled_type ) !== esc_attr( $wpform_email_type ) ? 'style=display:none' : '' ) . ' class="mo_registration_help_desc_internal" id="wpform_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $wpform_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a>
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your WPForm.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an Email Field to your form. Note the Field Label of the Email field.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Email Field Label below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . '
                            onclick="add_wpform(\'email\',1);" class="mo-form-button secondary" />&nbsp;

                            <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_wpform(1);" class="mo-form-button secondary" />
                            <br/><br/>';

							$form_results = get_multiple_form_select( $wpform_list_of_forms_otp_enabled, false, true, $disabled, 1, 'wpform', 'Label' );
							$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

echo '              </ol>
                </div>


                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="wp_form_phone"
                        class="app_enable" data-toggle="wpform_phone_option" name="mo_customer_validation_wpform_enable_type" 
                        value="' . esc_attr( $wpform_phone_type ) . '"' . ( esc_attr( $wpform_enabled_type ) === esc_attr( $wpform_phone_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>
                    
                <div ' . ( esc_attr( $wpform_enabled_type ) !== esc_attr( $wpform_phone_type ) ? 'style=display:none' : '' ) . ' class="mo_registration_help_desc_internal"
                    id="wpform_phone_option" ' . esc_attr( $disabled ) . '">
                    <ol>
                        <li><a href="' . esc_url( $wpform_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a>
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your WPForm.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add a <b>Phone Field</b> to your form. Note the Field Label of the Phone field.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Set the Format of the Phone Field to <u>International only</u>.' ), array( 'u' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Phone Field Label below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_wpform(\'phone\',2);
                                " class="mo-form-button secondary" />&nbsp; <input type="button" value="-" ' . esc_attr( $disabled ) . ' \
                                onclick="remove_wpform(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $wpform_list_of_forms_otp_enabled, false, true, $disabled, 2, 'wpform', 'Label' );
								$counter2     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '</ol>
                    </div> 
                    <div id="input-field-texts" class="pt-mo-4 flex" >
                    <div style="margin-left:2%;">
                        <div class="mo-input-wrapper">
                            <label class="mo-input-label">' . esc_html( mo_( 'Send OTP Button text' ) ) . '</label>
                            <input class=" mo-form-input" 
                                placeholder="Enter the verification button text" 
                                value="' . esc_attr( $button_text ) . '" 
                                type="text" name="mo_customer_validation_wpforms_sendotp_button_text" >
                        </div>
                    </div>  
                    <div style="margin-left:2%;">
                         <div class="mo-input-wrapper">
                            <label class="mo-input-label">' . esc_html( mo_( 'Enter OTP field text' ) ) . '</label>
                            <input class=" mo-form-input" 
                                placeholder="Enter the verification button text" 
                                value="' . esc_attr( $enter_otp_text ) . '" 
                                type="text" name="mo_customer_validation_wpforms_enterotp_field_text" >
                        </div>
                    </div> 
                    <div style="margin-left:2%;">
                        <div class="mo-input-wrapper">
                            <label class="mo-input-label">' . esc_html( mo_( 'Verify OTP Button Text' ) ) . '</label>
                            <input class=" mo-form-input" 
                                placeholder="Enter the verification button text" 
                                value="' . esc_attr( $verify_button_text ) . '" 
                                type="text" name="mo_customer_validation_wpforms_verify_button_text" >
                        </div> 
                    </div>  
                 </div>
                </div>
        </div>';

		multiple_from_select_script_generator( false, true, 'wpform', 'Label', array( $counter1, $counter2, 0 ) );
