<?php
/**
 * Load admin view for save settings button.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '<div class="mo_registration_settings_save_float static">
			<h2>' . esc_html( mo_( 'Save your settings.' ) ) . '</h2>
			<input type="button" id="ov_settings_button_float"  
						value="' . esc_attr( mo_( 'Save' ) ) . '" style="margin-bottom:2%;"' . esc_attr( $disabled ) . '
						class="button button-primary button-large" />
		</div>';
