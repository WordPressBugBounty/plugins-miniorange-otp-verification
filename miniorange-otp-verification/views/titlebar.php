<?php
/**
 * Load admin view for titlebar.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Handler\MoActionHandlerHandler;

echo '
<!-- Title Bar -->
<div>
	<div>
        <!--<img style="float:left;" src="' . esc_url( MOV_LOGO_URL ) . '"></div>-->
		<div class="mo-section-header">
			<h5 class="text-lg font-bold" style="flex: 1 1 0%;">' . esc_html( mo_( 'OTP Verification' ) ) . '</h5>';
echo '      
	        <div class="mo-otp-help-button static">';

if ( $is_logged_in && ( 'MoGateway' === $gateway_type || $is_free_plugin ) ) {
	echo '
			<div class="flex text-white text-xs">
				<div id="mo_check_transactions" class="mo-transaction-show ' . esc_attr( $active_class ) . '">
					Email:' . esc_attr( $remaining_email ) . '  |  SMS: ' . esc_attr( $remaining_sms ) . '		
					<button class="mo-refresh-btn ' . esc_attr( $active_class ) . '">
						<svg width="18" height="18" viewBox="0 0 512 512">
							<path d="M320,146s24.36-12-64-12A160,160,0,1,0,416,294" style="fill:none;stroke:#000;stroke-linecap:square;stroke-miterlimit:10;stroke-width:32px"/>
							<polyline points="256 58 336 138 256 218" style="fill:none;stroke:#000;stroke-linecap:square;stroke-miterlimit:10;stroke-width:32px"/>
						</svg>
					</button>
				</div>
				<div> 
					<a href="' . esc_url( MOV_PORTAL ) . '/initializePayment?requestOrigin=wp_otp_verification_basic_plan" target="_blank" type="button" class="mo-button recharge">Recharge</a>
				</div>
			</div>';
}
echo '
        </div>
    </div>
	<form id="mo_check_transactions_form" style="display:none;" action="" method="post">';

			wp_nonce_field( 'mo_check_transactions_form', '_nonce' );
echo '<input type="hidden" name="option" value="mo_check_transactions" />
        </form></div>';

if ( $is_logged_in && is_array( $modal_notice ) && $remaining_sms + $remaining_email <= 50 && ( 'MoGateway' === $gateway_type || $is_free_plugin ) ) {
	$remaining_total = $remaining_sms + $remaining_email;
	foreach ( $modal_notice as $key => $value ) {
		if ( in_array( $value, $modal_notice, true ) && $remaining_total <= $value && $remaining_total >= $key ) {
			MoActionHandlerHandler::mo_check_transactions();
			show_low_transaction_alert( $remaining_sms, $remaining_email, $key, $license_plan );
			break;
		}
	}
} else {
	$array = array(
		'21' => '50',
		'2'  => '10',
		'0'  => '1',
	);
	update_mo_option( 'mo_transaction_notice', $array );
}

if ( 'mo_hide_sms_notice' !== $is_sms_notice_closed ) {
	echo '<div  style="border: none;"
						class="notice mo_sms_notice is-dismissible font-normal rounded-smooth bg-blue-50 py-mo-3">
						<h2>Due to recent changes in the SMS Delivery rules by the government of some countries like Singapore, Vietnam, etc , you might face issues with SMS Delivery. In this case, contact us at <a style="cursor:pointer;" class="text-green-800 font-semibold" onclick="otpSupportOnClick();">otpsupport@xecurify.com</a>.</h2>
		  </div>';
}
