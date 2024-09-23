<?php
/**
 * Load admin view for Menberpress Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">
	        <input  type="checkbox" ' . esc_attr( $disabled ) . ' 
	                id="mrp" 
	                class="app_enable"  
	                data-toggle="mrp_options" 
	                name="mo_customer_validation_mrp_default_enable" 
	                value="1" ' . esc_attr( $mrp_registration ) . ' />
			<strong>' . esc_html( $form_name ) . '</strong>';

echo '	<div class="mo_otp_note ml-mo-4">
			' . wp_kses( mo_( 'Enable OTP verification on either Memberpress Single Checkout or Memberpress Registration Form.' ), array( 'br' => array() ) ) . ' </div> 
			<div class="mo_registration_help_desc" id="mrp_options">
				<div>
					<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b></div>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="mrp_phone" 
					        class="app_enable" 
					        data-toggle="mrp_phone_options" 
					        name="mo_customer_validation_mrp_enable_type" 
					        value="' . esc_attr( $mrpreg_phone_type ) . '"
						    ' . ( esc_attr( $mrp_default_type ) === esc_attr( $mrpreg_phone_type ) ? 'checked' : '' ) . '/>
				    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				
				<div ' . ( esc_attr( $mrp_default_type ) !== esc_attr( $mrpreg_phone_type ) ? 'hidden' : '' ) . ' 
				     class="mo_registration_help_desc_internal" 
					 id="mrp_phone_options" >' . esc_html( mo_( 'Follow the below steps to enable Phone Verification' ) ) . ':
					<ol>
						<li><a href="' . esc_url( $mrp_fields ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click here' ) ) . '</a> ' . esc_html( mo_( ' to add the list of fields.' ) ) . '</li>
						<li>' . wp_kses( mo_( 'Add a new by clicking the <b>Add New Field</b> button.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Give the <b>Field Name</b> as a Phone Field.' ), array( 'b' => array() ) ) . '</li>		
						<li>' . wp_kses( mo_( 'Select the field type as <b>Text Field</b> from the select box.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Select <b>Show at Signup</b> and <b>Required</b> from the select box to the right.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Copy the <b>Slug Name</b> of the phone field and enter it below:' ), array( 'b' => array() ) ) . '</li>
							<div class="pt-mo-4">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Slug Name' ) ) . '</label>
									<input class=" mo-form-input w-[40%]" id="mo_customer_validation_mrp_phone_field_key_1_1" placeholder="Enter Slug Name of the phone field" value="' . esc_attr( $mrp_field_key ) . '" type="text" name="mo_customer_validation_mrp_phone_field_key" >
								</div>
							</div>
						<li>' . wp_kses( mo_( 'Click on <b>Update Options</b> button to save your new field.' ), array( 'b' => array() ) ) . '</li>
						</li>
					</ol>
				</div>
				
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="mrp_email" 
					        class="app_enable" 
					        name="mo_customer_validation_mrp_enable_type" 
					        value="' . esc_attr( $mrpreg_email_type ) . '"
						    ' . ( esc_attr( $mrp_default_type ) === esc_attr( $mrpreg_email_type ) ? 'checked' : '' ) . '/>
					<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
                
                <div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="mrp_both" 
					        class="app_enable" 
					        data-toggle="mrp_both_options" 
					        name="mo_customer_validation_mrp_enable_type" 
					        value="' . esc_attr( $mrpreg_both_type ) . '"
						    ' . ( esc_attr( $mrp_default_type ) === esc_attr( $mrpreg_both_type ) ? 'checked' : '' ) . '/>
				    <strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

					echo '    		</div>
				
				<div ' . ( esc_attr( $mrp_default_type ) !== esc_attr( $mrpreg_both_type ) ? 'hidden' : '' ) . ' 
				     class="mo_registration_help_desc_internal" 
					 id="mrp_both_options" >' . esc_html( mo_( 'Follow the following steps to allow both Email and Phone Verification' ) ) . ':
					<ol>
						<li><a href="' . esc_url( $mrp_fields ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click here' ) ) . '</a> ' . esc_html( mo_( ' to add the list of fields.' ) ) . '</li>
						<li>' . wp_kses( mo_( 'Add a new by clicking the <b>Add New Field</b> button.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Give the <b>Field Name</b> as a Phone Field.' ), array( 'b' => array() ) ) . '</li>		
						<li>' . wp_kses( mo_( 'Select the field type as <b>Text Field</b> from the select box.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Select <b>Show at Signup</b> and <b>Required</b> from the select box to the right.' ), array( 'b' => array() ) ) . '</li>
						<li>' . wp_kses( mo_( 'Copy the <b>Slug Name</b> of the phone field and enter it below:' ), array( 'b' => array() ) ) . '</li>
							<div class="pt-mo-4">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Slug Name' ) ) . '</label>
									<input class=" mo-form-input w-[40%]" id="mo_customer_validation_mrp_phone_field_key_2_1" placeholder="Enter Slug Name of the phone field" value="' . esc_attr( $mrp_field_key ) . '" type="text" name="mo_customer_validation_mrp_phone_field_key" >
								</div>
							</div>
						</li>	
						<li>' . wp_kses( mo_( 'Click on <b>Update Options</b> button to save your new field.' ), array( 'b' => array() ) ) . '</li>
					</ol>
				</div>
				<div>
					<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
							name="mo_customer_validation_mpr_anon_only" 
							value="1" ' . esc_attr( $mpr_anon_only ) . '/>
					<strong>' . esc_html( mo_( 'Apply OTP Verification only for non-logged in users.' ) ) . '</strong>
				</div>
			</div>
		</div>';
