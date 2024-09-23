<?php
/**
 * Load admin view for Simplr Registration form.
 *
 * @package miniorange-otp-verification/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="simplr_default" data-toggle="simplr_default_options" class="app_enable" name="mo_customer_validation_simplr_default_enable" value="1"
				' . esc_attr( $simplr_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '			<div class="mo_registration_help_desc" id="simplr_default_options">
					<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
						<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="simplr_phone_instruction" id="simplr_phone" class="form_options app_enable" name="mo_customer_validation_simplr_enable_type" value="' . esc_attr( $simplr_type_phone ) . '"
							' . ( esc_attr( $simplr_enabled_type ) === esc_attr( $simplr_type_phone ) ? 'checked' : '' ) . ' />
								<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>';

echo '						<div ' . ( esc_attr( $simplr_enabled_type ) !== esc_attr( $simplr_type_phone ) ? 'hidden' : '' ) . ' id="simplr_phone_instruction" class="mo_registration_help_desc_internal">
								' . esc_html( mo_( 'Follow the following steps to enable Phone Verification' ) ) . ':
								<ol>
									<li><a href="' . esc_url( $simplr_fields_page ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '</li>
									<li>' . wp_kses( mo_( 'Add a new Phone Field by clicking the <b>Add Field</b> button.' ), array( 'b' => array() ) ) . '</li>
									<li>' . wp_kses( mo_( 'Give the <b>Field Name</b> and <b>Field Key</b> for the new field. Remember the Field Key as you will need it later.' ), array( 'b' => array() ) ) . '</li>
									<li>' . wp_kses( mo_( 'Click on <b>Add Field</b> button at the bottom of the page to save your new field.' ), array( 'b' => array() ) ) . '</li>
									<li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
									<li>' . wp_kses( mo_( 'Click on the <b>Edit</b> link of your page to modify it.' ), array( 'b' => array() ) ) . '</li>
									<li>' . esc_html( mo_( 'In the ShortCode add the following attribute' ) ) . ': <b>fields="{Field Key you provided in Step 2}"</b>. ' . esc_html( mo_( 'If you already have the fields attribute defined then just add the new field key to the list.' ) ) . '</li>
									<li>' . wp_kses( mo_( 'Click on <b>update</b> to save your page.' ), array( 'b' => array() ) ) . '</li>
									<li>' . esc_html( mo_( 'Enter the Field Key of the phone field.' ) ) . '
						<div class="flex gap-mo-4 mt-mo-4">
							<div>
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Field Key of Phone Field' ) ) . '</label>
									<input class=" mo-input" id="mo_customer_validation_simplr_phone_field_key1" value="' . esc_attr( $simplr_field_key ) . '" type="text" name="mo_customer_validation_simplr_phone_field_key" >
								</div>
							</div>
						</div>
					</li> 
								</ol>
							</div>
							</div>
							<div><input type="radio" ' . esc_attr( $disabled ) . ' id="simplr_email" class="form_options app_enable" name="mo_customer_validation_simplr_enable_type" value="' . esc_attr( $simplr_type_email ) . '"
									' . ( esc_attr( $simplr_enabled_type ) === esc_attr( $simplr_type_email ) ? 'checked' : '' ) . ' />
									<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
							</div>
							<div><input type="radio" ' . esc_attr( $disabled ) . ' data-toggle="simplr_both_instruction" id="simplr_both" class="form_options app_enable" name="mo_customer_validation_simplr_enable_type" value="' . esc_attr( $simplr_type_both ) . '"
									' . ( esc_attr( $simplr_enabled_type ) === esc_attr( $simplr_type_both ) ? 'checked' : '' ) . ' />
									<strong>' . esc_html( mo_( 'Let the user choose' ) ) . '</strong>';

									echo '							<div ' . ( esc_attr( $simplr_enabled_type ) !== esc_attr( $simplr_type_both ) ? 'hidden' : '' ) . ' id="simplr_both_instruction" class="mo_registration_help_desc_internal">
									' . esc_html( mo_( 'Follow the following steps to enable Email and Phone Verification' ) ) . ':
									<ol>
										<li><a href="' . esc_url( $simplr_fields_page ) . '" target="_blank">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of fields' ) ) . '
										<li>' . wp_kses( mo_( 'Add a new Phone Field by clicking the <b>Add Field</b> button.' ), array( 'b' => array() ) ) . '</li>
										<li>' . wp_kses( mo_( 'Give the <b>Field Name</b> and <b>Field Key</b> for the new field. Remember the Field Key as you will need it later.' ), array( 'b' => array() ) ) . '</li>
										<li>' . wp_kses( mo_( 'Click on <b>Add Field</b> button at the bottom of the page to save your new field.' ), array( 'b' => array() ) ) . '</li>
										<li><a href="' . esc_url( $page_list ) . '" target="_blank" class="mo_links">' . esc_html( mo_( 'Click Here' ) ) . '</a> ' . esc_html( mo_( ' to see your list of pages' ) ) . '</li>
										<li>' . wp_kses( mo_( 'Click on the <b>Edit</b> link of your page to modify it.' ), array( 'b' => array() ) ) . '</li>
										<li>' . esc_html( mo_( 'In the ShortCode add the following attribute' ) ) . ': <b>fields="{Field Key you provided in Step 2}"</b>. ' . esc_html( mo_( 'If you already have the fields attribute defined then just add the new field key to the list.' ) ) . '</li>
										<li>' . wp_kses( mo_( 'Click on <b>update</b> to save your page.' ), array( 'b' => array() ) ) . '</li>
										<li>' . esc_html( mo_( 'Enter the Field Key of the phone field.' ) ) . '
						<div class="flex gap-mo-4 mt-mo-4">
							<div>
								<div class="mo-input-wrapper">
									<label class="mo-input-label">' . esc_html( mo_( 'Field Key of Phone Field' ) ) . '</label>
									<input class=" mo-input" id="mo_customer_validation_simplr_phone_field_key2" value="' . esc_attr( $simplr_field_key ) . '" type="text" name="mo_customer_validation_simplr_phone_field_key" >
								</div>
							</div>
						</div>
					</li> 
									</ol>
								</div>
							</div>
						</div>
					</div>';
