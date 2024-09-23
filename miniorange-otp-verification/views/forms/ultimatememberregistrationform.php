<?php
/**
 * Load admin view for Ultimate Member Registration form.
 *
 * @package miniorange-otp-verification/views
 */

use OTP\Helper\MoMessages;

echo '		<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
		        <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
		                id="um_default" 
		                data-toggle="um_default_options" 
		                class="app_enable" 
		                name="mo_customer_validation_um_default_enable" 
		                value="1"
					    ' . esc_attr( $um_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="um_default_options">
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				
				<div>
                    <input  type ="checkbox" 
                            ' . esc_attr( $disabled ) . ' 
                            id ="um_mo_view" 
                            data-toggle = "um_mo_ajax_view_option" 
                            class="app_enable" 
                            name = "mo_customer_validation_um_is_ajax_form" 
                            value= "1"
                            ' . esc_attr( $is_ajax_mode_enabled ) . '/>
                    <Strong>' . esc_html( mo_( 'Do not show a popup. Validate user on the form itself.' ) ) . '</strong>
                    
                    <!--------------------------------------------------------------------------------------------
                                                           UM AJAX OPTIONS
                    --------------------------------------------------------------------------------------------->
                    <div  ' . ( esc_attr( $is_ajax_form ) ? '' : 'style=display:none' ) . ' 
                           id="um_mo_ajax_view_option" 
                           class="mo_registration_help_desc_internal">
                        <div class="mo_otp_note" style="color:red">
                            ' . esc_html(
						mo_(
							'This mode does not work with Let the user choose option. 
                                    Please use either phone or email only.'
						)
					) . '
                        </div>   
                        ' . esc_html( mo_( 'You will need to add a verification field on your form, for users to enter their OTP.' ) ) . '
                        <ol>
							<li>
							    <a href="' . esc_url( $um_forms ) . '"  target="_blank" class="mo_links">' .
									esc_html( mo_( 'Click Here' ) ) .
								'</a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '
							</li>
							<li>' . wp_kses( mo_( 'Click on the <b>Edit link</b> of your form.' ), array( 'b' => array() ) ) . '</li>
							<li>
							    ' . wp_kses( mo_( 'Add a new <b>OTP Verification</b> Field. Note the meta key and enter it below.' ), array( 'b' => array() ) ) . '
                            </li>
							<li>' . wp_kses( mo_( 'Click on <b>update</b> to save your form.' ), array( 'b' => array() ) ) . '</li>
						</ol>
						<div style="margin-left:2%;">
							<div class="pt-mo-4">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Verification Field Meta Key' ) ) . '</label>
									<input class=" mo-form-input" 
										placeholder="Enter the verification field meta key" 
										value="' . esc_attr( $um_otp_meta_key ) . '" 
										type="text" name="mo_customer_validation_um_verify_meta_key" >
								</div>
							</div>				
                        </div>
                        <div style="margin-left:2%;">
							<div class="pt-mo-4">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Verification Button text' ) ) . '</label>
									<input class=" mo-form-input" 
										placeholder="Enter the verification button text" 
										value="' . esc_attr( $um_button_text ) . '" 
										type="text" name="mo_customer_validation_um_button_text" >
								</div>
							</div>	
                        </div>
                    </div>
			    </div>
			    
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="um_phone" 
					        data-toggle="um_phone_instructions" 
					        class="app_enable" 
					        name="mo_customer_validation_um_enable_type" 
					        value="' . esc_attr( $um_type_phone ) . '"
					        ' . ( esc_attr( $um_enabled_type ) === esc_attr( $um_type_phone ) ? 'checked' : '' ) . '/>
				    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
					
					<div ' . ( esc_attr( $um_enabled_type ) !== esc_attr( $um_type_phone ) ? 'style=display:none' : '' ) . ' 
					     id="um_phone_instructions" 
					     class="mo_registration_help_desc_internal">
						 ' . esc_html( mo_( 'Follow the following steps to enable Phone Verification' ) ) . ':
						<ol>
							<li>
							    <a href="' . esc_url( $um_forms ) . '" target="_blank" class="mo_links">' .
									esc_html( mo_( 'Click Here' ) ) . '
							    </a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '
							</li>
							<li>' . wp_kses( mo_( 'Click on the <b>Edit link</b> of your form.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Add a new <b>Mobile Number</b> Field from the list of predefined fields.' ), array( 'b' => array() ) ) . '</li>
							<li>' . esc_html( mo_( 'Enter the phone User Meta Key.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
										<input class=" mo-input" id="mo_customer_validation_um_phone_key_1_0" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $um_register_field_key ) . '" type="text" name="mo_customer_validation_um_phone_key" >
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
							<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
							        name="mo_customer_validation_um_restrict_duplicates" 
							        value="1"' . esc_attr( $um_restrict_duplicates ) . '/>
							 <strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>
						
				        
					</div>
				</div>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="um_email" 
					        class="app_enable" 
					        name="mo_customer_validation_um_enable_type" 
					        value="' . esc_attr( $um_type_email ) . '"
					        ' . ( esc_attr( $um_enabled_type ) === esc_attr( $um_type_email ) ? 'checked' : '' ) . ' />
				    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="um_both" 
					        data-toggle="um_both_instructions" 
					        class="app_enable" 
					        name="mo_customer_validation_um_enable_type" 
					        value="' . esc_attr( $um_type_both ) . '"
						    ' . ( esc_attr( $um_enabled_type ) === esc_attr( $um_type_both ) ? 'checked' : '' ) . ' />
				    <strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

									echo '				
                    <div ' . ( esc_attr( $um_enabled_type ) !== esc_attr( $um_type_both ) ? 'style=display:none' : '' ) . ' 
                        id="um_both_instructions" 
                        class="mo_registration_help_desc_internal">
						' . esc_html( mo_( 'Follow the following steps to enable Email and Phone Verification' ) ) . ':
						<ol>
							<li>
							    <a href="' . esc_url( $um_forms ) . '" target="_blank" class="mo_links">' .
									esc_html( mo_( 'Click Here' ) ) . '
							    </a> ' . esc_html( mo_( ' to see your list of forms' ) ) . '
							</li>
							<li>' . wp_kses( mo_( 'Click on the <b>Edit link</b> of your form.' ), array( 'b' => array() ) ) . '</li>
							<li>' . wp_kses( mo_( 'Add a new <b>Mobile Number</b> Field from the list of predefined fields.' ), array( 'b' => array() ) ) . '</li>
							<li>' . esc_html( mo_( 'Enter the phone User Meta Key.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
										<input class=" mo-input" id="mo_customer_validation_um_phone_key_3_0" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $um_register_field_key ) . '" type="text" name="mo_customer_validation_um_phone_key" >
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
						<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
						        name="mo_customer_validation_um_restrict_duplicates" 
						        value="1"' . esc_attr( $um_restrict_duplicates ) . '/>
						<strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>
					</div>
				</div>
			</div>
		</div>';
