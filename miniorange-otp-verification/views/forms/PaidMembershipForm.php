<?php
/**
 * Load admin view for Paid Membership form.
 *
 * @package miniorange-otp-verification/handler
 */

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="pmpro_reg" class="app_enable" data-toggle="pmpro_options" name="mo_customer_validation_pmpro_enable" value="1"
			' . esc_attr( $pmpro_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="pmpro_options">
				<b>Choose between Phone or Email Verification</b>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="pmpro_phone" class="app_enable" name="mo_customer_validation_pmpro_contact_type" value="' . esc_attr( $pmpro_type_phone ) . '"
						' . ( esc_attr( $pmpro_enabled_type ) === esc_attr( $pmpro_type_phone ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="pmpro_email" class="app_enable" name="mo_customer_validation_pmpro_contact_type" value="' . esc_attr( $pmpro_type_email ) . '"
						' . ( esc_attr( $pmpro_enabled_type ) === esc_attr( $pmpro_type_email ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
