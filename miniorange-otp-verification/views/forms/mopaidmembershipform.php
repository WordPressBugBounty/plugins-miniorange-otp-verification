<?php
/**
 * Load admin view for Paid Membership form.
 *
 * @package miniorange-otp-verification/views/forms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$disabled                  = isset( $disabled ) ? $disabled : '';
$form_name                 = isset( $form_name ) ? $form_name : '';
$pmpro_enabled             = isset( $pmpro_enabled ) ? $pmpro_enabled : '';
$pmpro_enabled_type        = isset( $pmpro_enabled_type ) ? $pmpro_enabled_type : '';
$pmpro_type_phone          = isset( $pmpro_type_phone ) ? $pmpro_type_phone : 'pmpro_phone_enable';
$pmpro_type_email          = isset( $pmpro_type_email ) ? $pmpro_type_email : 'pmpro_email_enable';
$pmpro_restrict_duplicates = isset( $pmpro_restrict_duplicates ) ? $pmpro_restrict_duplicates : '';

echo '	<div class="mo_otp_form" id="' . esc_attr( get_mo_class( $handler ) ) . '"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="pmpro_reg" class="app_enable" data-toggle="pmpro_options" name="mo_customer_validation_pmpro_enable" value="1"
		' . esc_attr( $pmpro_enabled ) . ' /><strong>' . esc_html( $form_name ) . '</strong>';

echo '		<div class="mo_registration_help_desc" id="pmpro_options">
				<b>' . esc_html__( 'Choose between Phone or Email Verification', 'miniorange-otp-verification' ) . '</b>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="pmpro_phone" class="app_enable" data-toggle="pmpro_phone_options" name="mo_customer_validation_pmpro_contact_type" value="' . esc_attr( $pmpro_type_phone ) . '"
						' . ( $pmpro_enabled_type === $pmpro_type_phone ? 'checked' : '' ) . '/>
						<strong>' . esc_html__( 'Enable Phone Verification', 'miniorange-otp-verification' ) . '</strong>
				</div>
				<div    ' . ( $pmpro_enabled_type !== $pmpro_type_phone ? 'hidden' : '' ) . '  
                        class="mo_registration_help_desc_internal" 
						id="pmpro_phone_options" >
					<input  type="checkbox" ' . esc_attr( $disabled ) . '  
                            name="mo_customer_validation_pmpro_restrict_duplicates" value="1"
                            ' . esc_attr( $pmpro_restrict_duplicates ) . '  />
                    <strong>' . esc_html__( 'Do not allow users to use the same phone number for multiple accounts.', 'miniorange-otp-verification' ) . ' </strong>
				</div>
				<div>
					<input type="radio" ' . esc_attr( $disabled ) . ' id="pmpro_email" class="app_enable" name="mo_customer_validation_pmpro_contact_type" value="' . esc_attr( $pmpro_type_email ) . '"
						' . ( $pmpro_enabled_type === $pmpro_type_email ? 'checked' : '' ) . '/>
						<strong>' . esc_html__( 'Enable Email Verification', 'miniorange-otp-verification' ) . '</strong>
				</div>
			</div>
		</div>';