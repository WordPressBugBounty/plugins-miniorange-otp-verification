<?php
/**
 * Load admin view for MemberPress Single Checkout form.
 *
 * @package miniorange-otp-verification/views
 */

use OTP\Helper\MoUtility;


echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
	        <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
	                id="mrp_single" 
	                class="app_enable"  
	                data-toggle="mrp_single_options" 
	                name="mo_customer_validation_mrp_single_default_enable" 
	                value="1" ' . esc_attr( $mrp_single_registration ) . ' />
			<strong>' . esc_html( $form_name ) . '</strong>';

echo '	<div class="mo_otp_note ml-mo-4">
		' . wp_kses( mo_( 'Enable OTP verification on either Memberpress Registration or Memberpress Single Checkout Form.' ), array( 'br' => array() ) ) . ' </div> 
			<div class="mo_registration_help_desc" id="mrp_single_options">
				<div>
					<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b></div>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="mrp_single_phone" 
					        class="app_enable" 
					        data-toggle="mrp_single_phone_options" 
					        name="mo_customer_validation_mrp_single_enable_type" 
					        value="' . esc_attr( $mrp_singlereg_phone_type ) . '"
						    ' . ( esc_attr( $mrp_single_default_type ) === esc_attr( $mrp_singlereg_phone_type ) ? 'checked' : '' ) . '/>
				    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				
				<div ' . ( esc_attr( $mrp_single_default_type ) !== esc_attr( $mrp_singlereg_phone_type ) ? 'style=display:none' : '' ) . ' 
				     class="mo_registration_help_desc_internal" 
					 id="mrp_single_phone_options" >' . esc_html( mo_( 'Follow the below steps to enable Phone Verification' ) ) . ':
					<ol>
						<li><a href="' . esc_url( $mrp_single_fields ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click here' ) ) . '</a> ' . esc_html( mo_( ' to add the list of fields.' ) ) . '</li>
						<li>' . wp_kses( mo_( 'Add a new by clicking the <b>Add New Field</b> button.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Give the <b>Field Name</b> as a Phone Field.' ), array( 'b' => array() ) ) . '</li>		
						<li>' . wp_kses( mo_( 'Select the field type as <b>Text Field</b> from the select box.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Select <b>Show at Signup</b> and <b>Required</b> from the select box to the right.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Copy the <b>Slug Name</b> of the phone field and enter it below:' ), array( 'b' => array() ) ) . '</li>
							<div class="pt-mo-4">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Slug Name' ) ) . '</label>
									<input class=" mo-form-input w-[40%]" id="mo_customer_validation_mrp_single_phone_field_key_1_1" placeholder="Enter Slug Name of the phone field" value="' . esc_attr( $mrp_single_field_key ) . '" type="text" name="mo_customer_validation_mrp_single_phone_field_key" >
								</div>
							</div>
						</li>
						<li>' . wp_kses( mo_( 'Click on <b>Update Options</b> button to save your new field.' ), array( 'b' => array() ) ) . '</li>
					</ol>
				</div>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="mrp_single_email" 
					        class="app_enable" 
					        name="mo_customer_validation_mrp_single_enable_type" 
					        value="' . esc_attr( $mrp_singlereg_email_type ) . '"
						    ' . ( esc_attr( $mrp_single_default_type ) === esc_attr( $mrp_singlereg_email_type ) ? 'checked' : '' ) . '/>
					<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
