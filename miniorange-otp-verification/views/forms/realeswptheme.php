<?php
/**
 * Load admin view for Reals WP Theme form.
 *
 * @package miniorange-otp-verification/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="reales_reg" class="app_enable" data-toggle="reales_options" name="mo_customer_validation_reales_enable" value="1"
			' . esc_attr( $reales_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="reales_options">
				<b>Choose between Phone or Email Verification</b>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="reales_phone" class="app_enable" name="mo_customer_validation_reales_enable_type" value="' . esc_attr( $reales_type_phone ) . '"
						' . ( esc_attr( $reales_enable_type ) === esc_attr( $reales_type_phone ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="reales_email" class="app_enable" name="mo_customer_validation_reales_enable_type" value="' . esc_attr( $reales_type_email ) . '"
						' . ( esc_attr( $reales_enable_type ) === esc_attr( $reales_type_email ) ? 'checked' : '' ) . '/>
						<strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';

