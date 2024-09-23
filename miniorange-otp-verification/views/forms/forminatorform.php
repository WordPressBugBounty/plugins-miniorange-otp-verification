<?php
/**
 * Load admin view for Forminator form.
 *
 * @package miniorange-otp-verification/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoUtility;

echo '	
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="forminator_basic" class="app_enable" data-toggle="forminator_options" 
                name="mo_customer_validation_forminator_enable" value="1" ' . esc_attr( $is_forminator_enabled ) . ' />
                <strong>' . esc_html( $form_name ) . '</strong>
            <div class="mo_registration_help_desc" id="forminator_options">
                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="forminator_form_email" class="app_enable" 
                    data-toggle="forminator_email_option" name="mo_customer_validation_forminator_enable_type" 
                    value="' . esc_attr( $forminator_email_type ) . '" ' . ( esc_attr( $forminator_enabled_type ) === esc_attr( $forminator_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( esc_attr( $forminator_enabled_type ) !== esc_attr( $forminator_email_type ) ? 'style=display:none' : '' ) . ' class="mo_registration_help_desc_internal" id="forminator_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $forminator_form_list, 'url' ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your forminator Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Note the <b>Form ID</b> from the Form Settings Page.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add an <b>Email Field</b> to your form. Note the Field slug of the Email field.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Make sure the Email Field is required Field.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Email Field slug below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' 
                            onclick="add_forminator(\'email\',1);" class="mo-form-button secondary" />&nbsp;

                            <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_forminator(1);" class="mo-form-button secondary" />
                            <br/><br/>';

						$form_results = get_multiple_form_select( $forminator_list_of_forms_otp_enabled, false, true, $disabled, 1, 'forminator', 'Slug' );
						$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '              </ol>
                </div>


                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="forminator_form_phone" 
                        class="app_enable" data-toggle="forminator_phone_option" name="mo_customer_validation_forminator_enable_type" 
                        value="' . esc_attr( $forminator_phone_type ) . '"' . ( esc_attr( $forminator_enabled_type ) === esc_attr( $forminator_phone_type ) ? 'checked' : '' ) . ' />                                                                            
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>
                    
                <div ' . ( esc_attr( $forminator_enabled_type ) !== esc_attr( $forminator_phone_type ) ? 'style=display:none' : '' ) . ' class="mo_registration_help_desc_internal" 
                    id="forminator_phone_option" ' . esc_attr( $disabled ) . '">
                    <ol>
                        <li><a href="' . esc_url( $forminator_form_list, 'url' ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your forminator Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Note the <b>Form ID</b> from the Form Settings Page.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Add a <b>Phone Number</b> field to your form. Note the Field slug of the Phone field.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Make sure the Phone Field is required Field.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Phone Field slug below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_forminator(\'phone\',2);
                                " class="mo-form-button secondary" />&nbsp; <input type="button" value="-" ' . esc_attr( $disabled ) . ' 
                                onclick="remove_forminator(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $forminator_list_of_forms_otp_enabled, false, true, $disabled, 2, 'forminator', 'Slug' );
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
                                    type="text" name="mo_customer_validation_forminator_button_text" >
                            </div>
                        </div>
                    </div>             
                </div>
        </div>';

		multiple_from_select_script_generator( false, true, 'forminator', 'Slug', array( $counter1, $counter2, 0 ) );

