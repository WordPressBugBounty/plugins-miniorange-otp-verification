<?php
/**
 * View file for Customer Order Message
 *
 * @package miniorange-otp-verification/Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

	echo '
			<div id="custom_order_sms_meta_box">
				<input type="hidden" id="post_ID" name="post_ID" value="' . esc_attr( get_the_ID() ) . '">
				<div id="jsonMsg" hidden></div>

				<b>' . esc_html( mo_( 'Billing Phone: ' ) ) . '</b><br><br>
				<input type="text" id="billing_phone" class="mo-textarea" name="billing_phone" value="' . esc_attr( $phone_numbers ) . '" style="width:100%; border-color:#E2E8F0;"/><br><br>';


	echo ' 		<b>' . esc_html( mo_( 'SMS Template: ' ) ) . '</b><br>
				<p>
					<textarea type="text" name="mo_wc_custom_order_msg" id="mo_wc_custom_order_msg" class="mo-textarea w-full mo_remaining_characters" style="width: 100%;" 
						value=""  placeholder=" Write your message here.."></textarea>
						<span id="characters" style="font-size:12px;">Remaining Characters : <span id="remaining_mo_wc_custom_order_msg">160</span> </span>
				</p>
				<p>
              		<a class="mo-button inverted" id="mo_custom_order_send_message">' . esc_html( mo_( 'Send SMS' ) ) . '</a>
	        	</p>
			</div>

			<div class="mo_otp_note">
				<u>' . esc_html( mo_( 'Note for Indian Customers' ) ) . '</u> :
				' . esc_html( mo_( 'Please contact us on mfasupport@xecurify.com for sending Custom SMS.' ) ) . '

            </div>';
	echo '           

			<script>
				jQuery(document).ready(function () {  
					window.intlTelInput(document.querySelector("#billing_phone"));
				});
			</script>';
