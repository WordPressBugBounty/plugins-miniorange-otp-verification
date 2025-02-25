<?php
/**
 * Load admin view for Eduma Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoMessages;

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">' .
			'<input type="checkbox" 
					' . esc_attr( $disabled ) . '
					id="edumareg_default"
					class="app_enable"
					data-toggle="edumareg_options"
					name="mo_customer_validation_edumareg_enable"
					value="1"
					' . esc_attr( $edumareg_enabled ) . ' />
			<strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="edumareg_options">
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				<div>
					<input  type="radio" 
							' . esc_attr( $disabled ) . '
							id="edumareg_phone"
							class="app_enable"
							data-toggle="edumareg_phone_options"
							name="mo_customer_validation_edumareg_enable_type"
							value="' . esc_attr( $edumareg_type_phone ) . '"
							' . ( esc_attr( $edumareg_enabled_type ) === esc_attr( $edumareg_type_phone ) ? 'checked' : '' ) . '/>
					<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
					<div ' . ( esc_attr( $edumareg_enabled_type ) !== esc_attr( $edumareg_type_phone ) ? 'hidden' : '' ) . ' id="edumareg_phone_options"
						 class="mo_registration_help_desc_internal"
						id="edumareg_phone_options">
					' . esc_html( mo_( 'Follow the following steps to add a users phone number in the database' ) ) . ':
					<ol>
						<li>' . esc_html( mo_( 'Enter the phone User Meta Key.' ) ) . '
							<div class="flex gap-mo-4 mt-mo-4">
								<div>
									<div class="mo-input-wrapper">
										<label class="mo-input-label">' . esc_html( mo_( 'Phone User Meta Key' ) ) . '</label>
										<input class=" mo-form-input" id="mo_customer_validation_edumareg_phone_field_key" placeholder="Enter the phone User Meta Key" value="' . esc_attr( $edumareg_phone_field_key ) . '" type="text" name="mo_customer_validation_edumareg_phone_field_key" >
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
							<div class="mo_otp_note" style="margin-top:1%">
								' . esc_html(
											mo_(
												"If you don't know the metaKey against which the phone number " .
												'is stored for all your users then put the default value as telephone.'
											)
										) . '
							</div>
						</li>
						<li>' . esc_html( mo_( 'Click on the Save Button to save your settings.' ) ) . '</li>
					</ol>
					<input  type="checkbox" ' . esc_attr( $disabled ) . ' 
									name="mo_customer_validation_edumareg_restrict_duplicates"
									value="1"' . esc_attr( $edumareg_restrict_duplicates ) . '/>
							 <strong>' . esc_html( mo_( 'Do not allow users to use the same phone number for multiple accounts.' ) ) . '</strong>
					</div>
				</div>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
							id="edumareg_email"
							class="app_enable"
							name="mo_customer_validation_edumareg_enable_type"
							value="' . esc_attr( $edumareg_type_email ) . '"
							' . ( esc_attr( $edumareg_enabled_type ) === esc_attr( $edumareg_type_email ) ? 'checked' : '' ) . '/>
					<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
