<?php
/**
 * Load admin view for easy Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

echo '   
        <div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
            <input type="checkbox" ' . esc_attr( $disabled ) . ' id="easyreg_basic" class="app_enable" data-toggle="easyreg_options" 
                name="mo_customer_validation_easyreg_enable" value="1" ' . esc_attr( $is_easyreg_enabled ) . ' />
                <strong>' . esc_attr( $form_name ) . '</strong>
            <div class="mo_registration_help_desc" id="easyreg_options">
                <b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="easyreg_form_email" class="app_enable" 
                    data-toggle="easyreg_email_option" name="mo_customer_validation_easyreg_enable_type" 
                    value="' . esc_attr( $easyreg_email_type ) . '" ' . ( esc_attr( $easyreg_enabled_type ) === esc_attr( $easyreg_email_type ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                        
                
                <div ' . ( esc_attr( $easyreg_enabled_type ) !== esc_attr( $easyreg_email_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="easyreg_email_option"">
                    <ol>
                        <li><a href="' . esc_url( $easyreg_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your Easy Registration Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Note the Form ID from the Form Settings Page.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an Email Field to your form and click on Edit the field ' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an <b>verify_email</b> class in the field' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add a Verification Field to your form and add <b>verify_otp</b>' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Email Field Lebel and Verification Field Label below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' 
                            onclick="add_easyreg(\'email\',1);" class="mo-form-button secondary" />&nbsp;

                            <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_easyreg(1);" class="mo-form-button secondary" />
                            <br/><br/>';

						$form_results = get_multiple_form_select( $easyreg_list_of_forms_otp_enabled, true, true, $disabled, 1, 'easyreg', 'Label' );
						$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '              </ol>
                </div>


                <div>
                    <input type="radio" ' . esc_attr( $disabled ) . ' id="easyreg_form_phone" 
                        class="app_enable" data-toggle="easyreg_phone_option" name="mo_customer_validation_easyreg_enable_type" 
                        value="' . esc_attr( $easyreg_phone_type ) . '"' . ( esc_attr( $easyreg_enabled_type ) === esc_attr( $easyreg_phone_type ) ? 'checked' : '' ) . ' />                                                                            
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>
                    
                <div ' . ( esc_attr( $easyreg_enabled_type ) !== esc_attr( $easyreg_phone_type ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" 
                    id="easyreg_phone_option" ' . esc_attr( $disabled ) . '">
                    <ol>
                      <li><a href="' . esc_url( $easyreg_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> 
                            ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                        <li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of your Easy Registration Form.' ), array( 'b' => array() ) ) . '</li>
                        <li>' . esc_html( mo_( 'Note the Form ID from the Form Settings Page.' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an Phone Field to your form and click on Edit the field ' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add an <b>verify_phone</b> class in the field' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Add a Verification Field to your form and add <b>verify_otp</b>' ) ) . '</li>
                        <li>' . esc_html( mo_( 'Enter your Form ID, Email Field Lebel and Verification Field Label below' ) ) . ':<br>
                            <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_easyreg(\'phone\',2);
                                " class="mo-form-button secondary" />&nbsp; <input type="button" value="-" ' . esc_attr( $disabled ) . ' 
                                onclick="remove_easyreg(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $easyreg_list_of_forms_otp_enabled, true, true, $disabled, 2, 'easyreg', 'ID' );
								$counter2     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '</ol>
                    </div>
                    <div class="pt-mo-4">
                        <div class="mo-input-wrapper">
                            <label class="mo-input-label">' . esc_html( mo_( 'Verification Button text' ) ) . '</label>
                            <input class=" mo-form-input w-[40%]" placeholder="Enter the verification button text" value="' . esc_attr( $button_text ) . '" type="text" name="mo_customer_validation_easyreg_button_text" >
                        </div>
                    </div>            
                </div>
        </div>';

		multiple_from_select_script_generator( true, true, 'easyreg', 'ID', array( $counter1, $counter2, 0 ) );

