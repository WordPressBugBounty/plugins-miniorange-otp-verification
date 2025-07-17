<?php
/**
 * Load admin view for Profile made easy Registration form.
 *
 * @package miniorange-otp-verification/views
 */

use OTP\Helper\MoMessages;


echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
							<input type="checkbox" ' . esc_attr( $disabled ) . ' id="upme_default" class="app_enable" data-toggle="upme_default_options" name="mo_customer_validation_upme_default_enable" value="1"
								 ' . esc_attr( $upme_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '								<div class="mo_registration_help_desc" id="upme_default_options">
									<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="upme_phone_instructions" id="upme_phone" class="form_options app_enable" name="mo_customer_validation_upme_enable_type" value="' . esc_attr( $upme_type_phone ) . '"
										' . ( esc_attr( $upme_enable_type ) === esc_attr( $upme_type_phone ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>';

echo '									</div>
										<div ' . ( esc_attr( $upme_enable_type ) !== esc_attr( $upme_type_phone ) ? 'hidden' : '' ) . ' id="upme_phone_instructions" class="mo_registration_help_desc_internal">
											' . esc_html( mo_( 'Follow the following steps to enable Phone Verification' ) ) . ':
											<ol>
												<li><a href="' . esc_url( $upme_field_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on <b>Click here to add new field</b> button to add a new phone field.' ), array( 'b' => array() ) ) . ' </li>
												<li>' . esc_html( mo_( 'Fill up the details of your new field and click on <b>Submit New Field</b>.' ) ) . ' </li>
												<li>' . wp_kses( mo_( 'Keep the <b>Meta Key</b> handy as you will need it later on.' ), array( 'b' => array() ) ) . ' </li>
												<li>' . esc_html( mo_( 'Enter the Meta Key of the phone field.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone Field Meta Key' ) ) . '</label>
										<input class=" mo-input" id="mo_customer_validation_upme_phone_field_key" placeholder="Enter the Meta Key of the phone field" value="' . esc_attr( $upme_field_key ) . '" type="text" name="mo_customer_validation_upme_phone_field_key" >
									</div>
								</div>
								<div>';

								mo_draw_tooltip(
									MoMessages::showMessage( MoMessages::META_KEY_HEADER ),
									MoMessages::showMessage( MoMessages::META_KEY_BODY )
								);
								echo '
								</div>
							</div>
							<div class="mo_otp_note" style="margin-top:1%; width: 70%;">' . esc_attr( mo_( "If you don't know the metaKey against which the phone number is stored for all your users then put the default value as phone." ) ) . ' </div>
						</li> 
											</ol>
										</div>

									<div><input type="radio" ' . esc_attr( $disabled ) . ' id="upme_email" class="form_options app_enable" name="mo_customer_validation_upme_enable_type" value="' . esc_attr( $upme_type_email ) . '"
										' . ( esc_attr( $upme_enable_type ) === esc_attr( $upme_type_email ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
									</div>
									<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="upme_both_instructions" id="upme_both" class="form_options app_enable" name="mo_customer_validation_upme_enable_type" value="' . esc_attr( $upme_type_both ) . '"
										' . ( esc_attr( $upme_enable_type ) === esc_attr( $upme_type_both ) ? 'checked' : '' ) . ' />
											<strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

										echo '									<div ' . ( esc_attr( $upme_enable_type ) !== esc_attr( $upme_type_both ) ? 'hidden' : '' ) . ' id="upme_both_instructions" class="mo_registration_help_desc_internal">
											' . esc_html( mo_( 'Follow the following steps to enable both Email and Phone Verification' ) ) . ':
											<ol>
												<li><a href="' . esc_url( $upme_field_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '</li>
												<li>' . wp_kses( mo_( 'Click on <b>Click here to add new field</b> button to add a new phone field.' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Fill up the details of your new field and click on <b>Submit New Field</b>.' ), array( 'b' => array() ) ) . '</li>
												<li>' . wp_kses( mo_( 'Keep the <b>Meta Key</b> handy as you will need it later on.' ), array( 'b' => array() ) ) . '</li>
												<li>' . esc_html( mo_( 'Enter the Meta Key of the phone field.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone Field Meta Key' ) ) . '</label>
										<input class=" mo-input" id="mo_customer_validation_upme_phone_field_key1" placeholder="Enter the Meta Key of the phone field" value="' . esc_attr( $upme_field_key ) . '" type="text" name="mo_customer_validation_upme_phone_field_key" >
									</div>
								</div>
								<div>';

										mo_draw_tooltip(
											MoMessages::showMessage( MoMessages::META_KEY_HEADER ),
											MoMessages::showMessage( MoMessages::META_KEY_BODY )
										);
										echo '
								</div>
							</div>
							<div class="mo_otp_note" style="margin-top:1%; width: 70%;">' . esc_attr( mo_( "If you don't know the metaKey against which the phone number is stored for all your users then put the default value as phone." ) ) . ' </div>
						</li> 
											</ol>
										</div>
									</div>
								</div>
							</div>';
