<?php
/**
 * Load admin view for Ultimate Social Login form.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


echo ' 	<div class="mo_otp_form"><input type="checkbox" ' . esc_attr( $disabled ) . ' id="mo_social_login_plugin" class="app_enable" name="mo_customer_validation_mo_social_login_enable" value="1"
			 ' . esc_attr( $mo_social_login_enabled ) . '/><strong>' . esc_html( mo_( 'miniOrange Social Login Integration' ) ) . '<i>' . esc_html( mo_( ' (SMS Verification only) ' ) ) . '</i></strong>

		</div>';
