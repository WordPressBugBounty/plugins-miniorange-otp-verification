<?php
/**
 * Load admin view for Ultimate Member Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '			<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="ultimatepro" class="app_enable" data-toggle="ultipro_options" name="mo_customer_validation_ultipro_enable" value="1"
										' . esc_attr( $ultipro_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';


echo '							<div class="mo_registration_help_desc" id="ultipro_options">
									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="ultipro_email" class="app_enable" data-toggle="ultipro_email_instructions" name="mo_customer_validation_ultipro_type" value="' . esc_attr( $umpro_type_email ) . '"
										' . ( esc_attr( $ultipro_enabled_type ) === esc_attr( $umpro_type_email ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
									</div>
									
										<div ' . ( esc_attr( $ultipro_enabled_type ) !== esc_attr( $umpro_type_email ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="ultipro_email_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Email Verification for Ultimate membership Pro Form' ) ) . ': 
											<ol>
												<li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of the page which has your Ultimate membership Pro registration form' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Add the following short code just below the given registration shortcode' ) ) . ': <code>[mo_email]</code> </li>
												<li><a href="' . esc_url( $umpro_custom_field_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( 'to see the list of your custom fields.' ) ) . '</li>
												<li>' . esc_html( mo_( "Add a custom text field with slug \"validate\" and label \"Enter Validation Code\" in your registration page.Use this text field to enter the OTP received. Make sure it's a required field." ) ) . '</li>								
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings.' ) ) . '</li>
											</ol>
									</div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="ultipro_phone" class="app_enable" data-toggle="ultipro_phone_instructions" name="mo_customer_validation_ultipro_type" value="' . esc_attr( $umpro_type_phone ) . '"
										' . ( esc_attr( $ultipro_enabled_type ) === esc_attr( $umpro_type_phone ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
									</div>
									
										<div ' . ( esc_attr( $ultipro_enabled_type ) !== esc_attr( $umpro_type_phone ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" id="ultipro_phone_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification for Ultimate membership Pro Form' ) ) . ': 
											<ol>
												<li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on the <b>Edit</b> option of the page which has your Ultimate membership Pro registration form' ), array( 'b' => array() ) ) . '.</li>
												<li>' . esc_html( mo_( 'Add the following short code just below the given registration shortcode' ) ) . ': <code>[mo_phone]</code> </li>
												<li><a href="' . esc_url( $umpro_custom_field_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( 'to see the list of your custom fields.' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on the edit option for the <b>phone field</b> and change the field type to text. Click on save to save your settings.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Enable the phone field for your registration form and make sure it is a required field.' ) ) . '</li>  
												<li>' . esc_html( mo_( "Add a custom text field with slug \"validate\" and label \"Enter Validation Code\" in your registration page. Use this text field to enter the OTP received. Make sure it's a required field." ) ) . '</li>								
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings.' ) ) . '</li>
											</ol>
									</div>

									
								</div>
							</div>';
