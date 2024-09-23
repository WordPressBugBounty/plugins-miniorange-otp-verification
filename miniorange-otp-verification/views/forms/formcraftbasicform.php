<?php
/**
 * Load admin view for FormCraft Basic form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="formcraft" class="app_enable" data-toggle="formcraft_options"
                name="mo_customer_validation_formcraft_enable" value="1"' . esc_attr( $formcraft_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="formcraft_options">
                <div><input type="radio" ' . esc_attr( $disabled ) . ' id="formcraft_email" class="app_enable" data-toggle="fcbe_instructions" name="mo_customer_validation_formcraft_enable_type" value="' . esc_attr( $formcraft_type_email ) . '"
                    ' . ( esc_attr( $formcraft_enabled_type ) === esc_attr( $formcraft_type_email ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
                </div>
                <div ' . ( esc_attr( $formcraft_enabled_type ) !== esc_attr( $formcraft_type_email ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="fcbe_instructions" >
                        ' . esc_html( mo_( 'Follow the following steps to enable Email Verification for FormCraft' ) ) . ':
                        <ol>
                            <li><a href="' . esc_url( $formcraft_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Click on the form to edit it.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Add an Email Field to your form. Note the Label of the email field.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Add an Verification Field to your form where users will enter the OTP received. Note the Label of the verification field.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Enter your Form ID, the label of the Email Field and Verification Field below' ) ) . ':<br>
                                <br/>' . esc_html( mo_( 'Add Form ' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_formcraft(\'email\',1);" class="mo-form-button secondary" />&nbsp;
                                <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_formcraft(1);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $formcraft_otp_enabled, true, true, $disabled, 1, 'formcraft', 'Label' );
								$counter1     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

echo '						</li>
                            <li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
                        </ol>
                </div>
                <div><input type="radio" ' . esc_attr( $disabled ) . ' id="formcraft_phone" class="app_enable" data-toggle="fcbp_instructions" name="mo_customer_validation_formcraft_enable_type" value="' . esc_attr( $formcraft_type_phone ) . '"
                    ' . ( esc_attr( $formcraft_enabled_type ) === esc_attr( $formcraft_type_phone ) ? 'checked' : '' ) . ' />
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
                </div>
                <div ' . ( esc_attr( $formcraft_enabled_type ) !== esc_attr( $formcraft_type_phone ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="fcbp_instructions" >
                        ' . esc_html( mo_( 'Follow the following steps to enable Phone Verification for FormCraft' ) ) . ':
                        <ol>
                            <li><a href="' . esc_url( $formcraft_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Click on the form to edit it.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Add a Phone Field to your form. Note the Label of the phone field.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Add an Verification Field to your form where users will enter the OTP received. Note the Label of the verification field.' ) ) . '</li>
                            <li>' . esc_html( mo_( 'Enter your Form ID, the label of the Email Field and Verification Field below' ) ) . ':<br>
                                <br/>' . esc_html( mo_( 'Add Form' ) ) . ': <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_formcraft(\'phone\',2);" class="mo-form-button secondary" />&nbsp;
                                <input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_formcraft(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $formcraft_otp_enabled, true, true, $disabled, 2, 'formcraft', 'Label' );
								$counter2     = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

echo '						</li>
                            <li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
                        </ol>
                </div>
            </div>
        </div>';

		multiple_from_select_script_generator( true, true, 'formcraft', 'Label', array( $counter1, $counter2, 0 ) );


