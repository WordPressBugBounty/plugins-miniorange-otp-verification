<?php
/**
 * Load admin view for UserUltraRegistrationForm.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="uultra_default" class="app_enable" data-toggle="uultra_default_options" name="mo_customer_validation_uultra_default_enable" value="1"
										' . esc_attr( $uultra_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '							<div class="mo_registration_help_desc" id="uultra_default_options">
									<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="uultra_phone_instructions" id="uultra_phone" class="form_options app_enable" name="mo_customer_validation_uultra_enable_type" value="' . esc_attr( $uultra_type_phone ) . '"
										' . ( esc_attr( $uultra_enable_type ) === esc_attr( $uultra_type_phone ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>';

echo '									<div ' . ( esc_attr( $uultra_enable_type ) !== esc_attr( $uultra_type_phone ) ? 'hidden' : '' ) . ' id="uultra_phone_instructions" class="mo_registration_help_desc_internal">
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification' ) ) . ':
											<ol>
												<li><a href="' . esc_url( $uultra_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on <b>Click here to add new field</b> button to add a new phone field.' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Fill up the details of your new field and click on <b>Submit New Field.</b>' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Keep the <b>Meta Key</b> handy as you will need it later on.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Enter the Meta Key of the phone field' ) ) . ': 
													<div class="pt-mo-4">
														<div class="mo-input-wrapper">
															<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
															<input class=" mo-form-input" id="mo_customer_validation_uultra_phone_field_key" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $uultra_field_key ) . '" type="text" name="mo_customer_validation_uultra_phone_field_key" >
														</div>
													</div>
												</li>
											</ol>
										</div>
									</div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="uultra_email" class="form_options app_enable" name="mo_customer_validation_uultra_enable_type" value="' . esc_attr( $uultra_type_email ) . '"
										 ' . ( esc_attr( $uultra_enable_type ) === esc_attr( $uultra_type_email ) ? 'checked' : '' ) . ' />
										 <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
									</div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="uultra_both_instructions" id="uultra_both" class="form_options app_enable" name="mo_customer_validation_uultra_enable_type" value="' . esc_attr( $uultra_type_both ) . '"
										' . ( esc_attr( $uultra_enable_type ) === esc_attr( $uultra_type_both ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

										echo '									<div ' . ( esc_attr( $uultra_enable_type ) !== esc_attr( $uultra_type_both ) ? 'hidden' : '' ) . ' id="uultra_both_instructions" class="mo_registration_help_desc_internal">
											' . esc_html( mo_( 'Follow the following steps to enable both Email and Phone Verification' ) ) . ':
											<ol>
												<li><a href="' . esc_url( $uultra_form_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on <b>Click here to add new field</b> button to add a new phone field.' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Fill up the details of your new field and click on <b>Submit New Field.</b>' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Keep the <b>Meta Key</b> handy as you will need it later on.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Enter the Meta Key of the phone field' ) ) . ': 
													<div class="pt-mo-4">
														<div class="mo-input-wrapper">
															<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
															<input class=" mo-form-input" id="mo_customer_validation_uultra_phone_field_key1" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $uultra_field_key ) . '" type="text" name="mo_customer_validation_uultra_phone_field_key" >
														</div>
													</div>
												</li>
											</ol>
										</div>
									</div>
								</div>
							</div>';
