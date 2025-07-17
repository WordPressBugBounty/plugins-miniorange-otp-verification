<?php
/**
 * Load admin view for WpEmemberForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

use OTP\Helper\MoUtility;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo ' 	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="emember_reg" class="app_enable" data-toggle="emember_default_options" name="mo_customer_validation_emember_default_enable" value="1"
										' . esc_attr( $emember_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '								<div class="mo_registration_help_desc" id="emember_default_options">
									<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
									<p><input type="radio" ' . esc_attr( $disabled ) . ' id="emember_phone" class="app_enable" name="mo_customer_validation_emember_enable_type" 
											value="' . esc_attr( $emember_type_phone ) . '" data-toggle="emember_phone_instructions"
										' . ( esc_attr( $emember_enable_type ) === esc_attr( $emember_type_phone ) ? 'checked' : '' ) . ' />
										<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
									</p>
									<div ' . ( esc_attr( $emember_enable_type ) !== esc_attr( $emember_type_phone ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" 
											id="emember_phone_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification for' ) ) . '
											eMember Form: 
											<ol>
												<li><a href="' . esc_url( $form_settings_link ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( 'to see your form settings.' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Go to the <b>Registration Form Fields</b> section.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Check the "Show phone field on registration page" option to show Phone field on your form.' ) ) . '</li>
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
											</ol>
									</div>
									<p><input type="radio" ' . esc_attr( $disabled ) . ' id="emember_email" class="app_enable" name="mo_customer_validation_emember_enable_type" value="' . esc_attr( $emember_type_email ) . '"
										' . ( esc_attr( $emember_enable_type ) === esc_attr( $emember_type_email ) ? 'checked' : '' ) . ' />
										<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
									</p>
									<p><input type="radio" ' . esc_attr( $disabled ) . ' id="emember_both" class="app_enable" name="mo_customer_validation_emember_enable_type" 
										value="' . esc_attr( $emember_type_both ) . '" data-toggle="emember_both_instructions"
										' . ( esc_attr( $emember_enable_type ) === esc_attr( $emember_type_both ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

											echo '										
									</p>
									<div ' . ( esc_attr( $emember_enable_type ) !== esc_attr( $emember_type_both ) ? 'hidden' : '' ) . ' class="mo_registration_help_desc_internal" 
											id="emember_both_instructions" >
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification for' ) ) . '
											eMember Form: 
											<ol>
												<li><a href="' . esc_url( $form_settings_link ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( 'to see your form settings.' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Go to the <b>Registration Form Fields</b> section.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Check the "Show phone field on registration page" option to show Phone field on your form.' ) ) . '</li>
												<li>' . esc_html( mo_( 'Click on the Save Button to save your settings' ) ) . '</li>
											</ol>
									</div>
								</div>
							</div>';
