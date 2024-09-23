<?php
/**
 * Load admin view for Registration Magic form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoUtility;

echo ' 	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="crf_default" class="app_enable" data-toggle="crf_default_options" name="mo_customer_validation_crf_default_enable" value="1"
				' . esc_attr( $crf_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '			<div class="mo_registration_help_desc" id="crf_default_options">
					<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
					<div><input type="radio" ' . esc_attr( $disabled ) . ' id="crf_phone" data-toggle="crf_phone_instructions" class="form_options app_enable" name="mo_customer_validation_crf_enable_type" value="' . esc_attr( $crf_type_phone ) . '"
						' . ( esc_attr( $crf_enable_type ) === esc_attr( $crf_type_phone ) ? 'checked' : '' ) . ' />
							<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>';

echo '					<div ' . ( esc_attr( $crf_enable_type ) !== esc_attr( $crf_type_phone ) ? 'hidden' : '' ) . ' id="crf_phone_instructions" class="mo_registration_help_desc_internal">
							' . esc_html( mo_( 'Follow the following steps to enable Phone Verification' ) ) . ':
							<ol>
								<li><a href="' . esc_url( $crf_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
								<li>' . wp_kses( mo_( 'Click on <b>fields</b> link of your form to see list of fields.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Choose <b>Text</b> field from the list. Please do not select Phone/Mobile Number.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Enter the <b>Label</b> of your new field. Keep this handy as you will need it later.' ), array( 'b' => array() ) ) . '</li>
								<li>' . esc_html( mo_( 'Navigate to Advanced settings.' ) ) . '</li>
								<li>' . wp_kses( mo_( 'Under RULES section check the box which says <b>Is Required</b>.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Enable <b>Define New User Meta Key</b> under <b>Add Field to WordPress User Profile</b> section.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Enter the meta key as <b>rm_phone_number</b>.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Click on <b>Save</b> button to save your new field.' ), array( 'b' => array() ) ) . '<br/>
								<br/>' . esc_html( mo_( 'Add Form' ) ) . ' : <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_crf(\'phone\',2);" class="mo-form-button secondary" />&nbsp;
								<input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_crf(2);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $crf_form_otp_enabled, false, true, $disabled, 2, 'crf', 'Label' );
								$crfcounter2  = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;
echo '											
								</li>								
								<li>' . esc_html( mo_( 'Click on the Save Button to save your settings and keep a track of your Form Ids.' ) ) . '</li>
							</ol>
							<input  type="checkbox" 
							        ' . esc_attr( $disabled ) . '
							        id="mo_customer_validation_crf_restrict_duplicates" 
							        name="mo_customer_validation_crf_restrict_duplicates" 
							        value="1"
							        ' . esc_attr( $restrict_duplicates ) . '/>
				            <strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>

						</div>
					</div>
					<div><input type="radio" ' . esc_attr( $disabled ) . ' id="crf_email" data-toggle="crf_email_instructions" class="form_options app_enable" name="mo_customer_validation_crf_enable_type" value="' . esc_attr( $crf_type_email ) . '"
						' . ( esc_attr( $crf_enable_type ) === esc_attr( $crf_type_email ) ? 'checked' : '' ) . ' />
						<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
					</div>
					<div ' . ( esc_attr( $crf_enable_type ) !== esc_attr( $crf_type_email ) ? 'hidden' : '' ) . ' id="crf_email_instructions" class="crf_form mo_registration_help_desc_internal">
						<ol>
							<li><a href="' . esc_url( $crf_form_list ) . '" target="_blank" class="mo_links" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
							<li>' . wp_kses( mo_( 'Click on <b>fields</b> link of your form to see  list of fields.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Choose <b>email</b> field from the list.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Enter the <b>Label</b> of your new field. Keep this handy as you will need it later.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Under RULES section check the box which says <b>Is Required</b>.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Click on <b>Save</b> button to save your new field.' ), array( 'b' => array() ) ) . '<br/>
							<br/>' . esc_html( mo_( 'Add Form' ) ) . ' : <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_crf(\'email\',1);" class="mo-form-button secondary"/>&nbsp;
								<input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_crf(1);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $crf_form_otp_enabled, false, true, $disabled, 1, 'crf', 'Label' );
								$crfcounter1  = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

echo '</li>
						
							
							<li>' . esc_html( mo_( 'Click on the Save Button to save your settings and keep a track of your Form Ids.' ) ) . '</li>
						</ol>
					</div>
					<div><input type="radio" ' . esc_attr( $disabled ) . ' id="crf_both" data-toggle="crf_both_instructions"  class="form_options app_enable" name="mo_customer_validation_crf_enable_type" value="' . esc_attr( $crf_type_both ) . '"
						' . ( esc_attr( $crf_enable_type ) === esc_attr( $crf_type_both ) ? 'checked' : '' ) . ' />
						<strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

						echo '				<div ' . ( esc_attr( $crf_enable_type ) !== esc_attr( $crf_type_both ) ? 'hidden' : '' ) . ' id="crf_both_instructions" class="mo_registration_help_desc_internal">
						' . esc_html( mo_( 'Follow the following steps to enable both Email and Phone Verification' ) ) . ':
						<ol>
							<li><a href="' . esc_url( $crf_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '</li>
							<li>' . wp_kses( mo_( 'Click on <b>fields</b> link of your form to see list of fields.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Choose <b>Text</b> field from the list. Please do not select Phone/Mobile Number.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Enter the <b>Label</b> of your new field. Keep this handy as you will need it later.' ), array( 'b' => array() ) ) . '</li>
								<li>' . esc_html( mo_( 'Navigate to Advanced settings.' ) ) . '</li>
								<li>' . wp_kses( mo_( 'Under RULES section check the box which says <b>Is Required</b>.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Enable <b>Associate with Existing User Meta Keys</b> under <b>Add Field to WordPress User Profile</b> section.' ), array( 'b' => array() ) ) . '</li>
								<li>' . wp_kses( mo_( 'Select your user meta key as <b>pmpro_bphone</b>.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Click on <b>Save</b> button to save your new field.' ), array( 'b' => array() ) ) . '<br/>
							<br/>' . esc_html( mo_( 'Add Form' ) ) . ' : <input type="button"  value="+" ' . esc_attr( $disabled ) . ' onclick="add_crf(\'both\',3);" class="mo-form-button secondary"/>&nbsp;
								<input type="button" value="-" ' . esc_attr( $disabled ) . ' onclick="remove_crf(3);" class="mo-form-button secondary" /><br/><br/>';

								$form_results = get_multiple_form_select( $crf_form_otp_enabled, false, true, $disabled, 3, 'crf', 'Label' );
								$crfcounter3  = ! MoUtility::is_blank( $form_results['counter'] ) ? max( $form_results['counter'] - 1, 0 ) : 0;

						echo '</li>
						
							
							<li>' . esc_html( mo_( 'Click on the Save Button to save your settings and keep a track of your Form Ids.' ) ) . '</li>
						</ol>
					</div>
				</div>
			</div>
		</div>';

						multiple_from_select_script_generator( false, true, 'crf', 'Label', array( $crfcounter1, $crfcounter2, $crfcounter3 ) );
