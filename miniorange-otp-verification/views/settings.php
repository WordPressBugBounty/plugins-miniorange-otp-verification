<?php
/**
 * Load admin view for settings of Configured Forms.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '	
			<form name="f" method="post" action="' . esc_url( $moaction ) . '" id="mo_otp_verification_settings">
			    <input type="hidden" id="error_message" name="error_message" value="" />
				<input type="hidden" name="option" value="mo_customer_validation_settings" />';

					wp_nonce_field( $nonce );
if ( $form_name && ! $show_configured_forms ) {
	include MOV_DIR . 'views/formsettings.php';
} else {
	include MOV_DIR . 'views/formlist.php';
}
require MOV_DIR . 'views/configuredforms.php';

echo '		</form>';
