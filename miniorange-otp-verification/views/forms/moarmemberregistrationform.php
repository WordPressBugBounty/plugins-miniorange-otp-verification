<?php
/**
 * Load admin view for AR member form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoMessages;


use OTP\Helper\MoUtility;

echo '	
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="armember_basic" class="app_enable" data-toggle="armember_options" 
                name="mo_customer_validation_armember_enable" value="1" ' . esc_attr( $is_armember_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
            <div class="mo_registration_help_desc" ' . esc_attr( $is_armember_hidden ) . ' id="armember_options">
                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="armember_form_email" class="app_enable" 
                    data-toggle="armember_email_option" name="mo_customer_validation_armember_enable_type" 
                    value="' . esc_attr( $armember_email_type ) . '" ' . ( esc_attr( $armember_enabled_type ) === esc_attr( $armember_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( esc_attr( $armember_enabled_type ) !== esc_attr( $armember_email_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="armember_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $armember_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b> Edit </b> Form option of your ARMember Registration Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Note the <b> Form ID </b> from the Form Settings Page.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add a Verification Field to your form where users will enter the OTP sent to their Email Address. Note the Field Metakey of the Verification field.' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Make sure that the Verification Field is <b>required Field</b>.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Please note that the default Email Field Metakey is <b> user_email </b>.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Default Email Field Metakey and Verification Field Metakey below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . '
                            onclick="add_armember(\'email\',1);" class="mo-form-button secondary" />&nbsp;

                            <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_armember(1);" class="mo-form-button secondary" />
                            <br/><br/>';
						$form_results = get_multiple_form_select( $armember_list_of_forms_otp_enabled, true, true, $disabled, 1, 'armember', 'Metakey' );
						$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

echo '              </ol>
                </div>


                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="armember_form_phone"
                        class="app_enable" data-toggle="armember_phone_option" name="mo_customer_validation_armember_enable_type"
                        value="' . esc_attr( $armember_phone_type ) . '"' . ( esc_attr( $armember_enabled_type ) === esc_attr( $armember_phone_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>

                <div ' . ( esc_attr( $armember_enabled_type ) !== esc_attr( $armember_phone_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal"
                    id="armember_phone_option" ' . esc_attr( $disabled ) . '">
                    <ol>
                        <li><a href="' . esc_url( $armember_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a>
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b> Edit </b> Form option of your ARMember Registration Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Note the <b> Form ID </b> from the Form Settings Page.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add a Phone Number field to your form. Note the <b> Field Metakey </b> of the Phone field.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add a Verification Field to your form where users will enter the OTP sent to their Phone. Note the Field Metakey of the Verification field.' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Make sure Both Phone Field and Verification Field are <b>required Fields</b>.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Phone Field Metakey and Verification Metakey below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_armember(\'phone\',2);
                                " class="mo-form-button secondary" />&nbsp; <input type="button" value="-" ' . esc_attr( $disabled ) . '
                                onclick="remove_armember(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $armember_list_of_forms_otp_enabled, true, true, $disabled, 2, 'armember', 'Metakey' );
								$counter2     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '</ol>
                    </div>
                    <div style="margin-left:2%;">
                        <div class="pt-mo-4">
                            <div class="mo-input-wrapper">
                                <label class="mo-input-label">' . esc_html( mo_( 'Verification Button text' ) ) . '</label>
                                <input class=" mo-form-input"
                                    placeholder="Enter the verification button text"
                                    value="' . esc_attr( $button_text ) . '"
                                    type="text" name="mo_customer_validation_armember_button_text" >
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

		multiple_from_select_script_generator( true, true, 'armember', 'Metakey', array( $counter1, $counter2, 0 ) );
