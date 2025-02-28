<?php
/**
 * Load admin view for DocDirect theme Registration form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '">' .
			'<input type="checkbox"
                    ' . esc_attr( $disabled ) . ' 
                    id="docdirect_default" 
                    class="app_enable" 
                    data-toggle="docdirect_options" 
                    name="mo_customer_validation_docdirect_enable" 
                    value="1"
			        ' . esc_attr( $docdirect_enabled ) . ' />
            <strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="docdirect_options">
				<b>' . esc_html( mo_( 'Choose between Phone or Email Verification' ) ) . '</b>
				<div>
					<input  type="radio" 
					        ' . esc_attr( $disabled ) . ' 
					        id="docdirect_phone" 
					        class="app_enable" 
					        data-toggle="docdirect_phone_options" 
					        name="mo_customer_validation_docdirect_enable_type" 
					        value="' . esc_attr( $docdirect_type_phone ) . '"
						    ' . ( esc_attr( $docdirect_enabled_type ) === esc_attr( $docdirect_type_phone ) ? 'checked' : '' ) . '/>
                    <strong>' . esc_html( mo_( 'Enable Phone Verification' ) ) . '</strong>
				</div>
				<div>
					<input  type="radio" ' . esc_attr( $disabled ) . ' 
					        id="docdirect_email" 
					        class="app_enable" 
					        name="mo_customer_validation_docdirect_enable_type" 
					        value="' . esc_attr( $docdirect_type_email ) . '"
						    ' . ( esc_attr( $docdirect_enabled_type ) === esc_attr( $docdirect_type_email ) ? 'checked' : '' ) . '/>
                    <strong>' . esc_html( mo_( 'Enable Email Verification' ) ) . '</strong>
				</div>
			</div>
		</div>';
