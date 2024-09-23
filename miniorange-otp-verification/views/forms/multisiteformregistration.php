<?php
/**
 * Load admin view for Multisite Form.
 *
 * @package miniorange-otp-verification/view
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="multisite_reg" class="app_enable" data-toggle="multisite_options" name="mo_customer_validation_multisite_enable" value="1"
			' . esc_attr( $multisite_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';


echo '		<div class="mo_registration_help_desc" id="multisite_options">
				<b>Choose between Phone or Email Verification</b>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="multisite_phone" class="app_enable" name="mo_customer_validation_multisite_contact_type" value="' . esc_attr( $multisite_type_phone ) . '"
						' . ( esc_attr( $multisite_enabled_type ) === esc_attr( $multisite_type_phone ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>


				</div>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="multisite_email" class="app_enable" name="mo_customer_validation_multisite_contact_type" value="' . esc_attr( $multisite_type_email ) . '"
						' . ( esc_attr( $multisite_enabled_type ) === esc_attr( $multisite_type_email ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
