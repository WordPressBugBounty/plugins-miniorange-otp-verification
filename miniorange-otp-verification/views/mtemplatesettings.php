<?php
/**
 * Loads View for List of all the addons.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\MoUtility;
use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;

echo '
		<div class="border-b px-mo-4" id="sms_template">
			<div class="w-full flex m-mo-4">
				<div class="flex-1">
					<h5 class="mo-title">' . esc_html( 'SMS Template Configurations' ) . '</h5>
					<p class="mo-caption mt-mo-2">' . esc_html( mo_( 'Customize your SMS template' ) ) . '</p>
				</div>
				<div class="flex-1">
					<div class="pr-mo-8 my-mo-6">
						<div class="mo_otp_note">
							To prevent spamming and spoofing, we have enabled secure customization of SMS templates. Contact us at <b>otpsupport@xecurify.com</b> with your desired template and target country.
						</div>
					</div>
					<div class="pr-mo-8 my-mo-6">
						<div class="mo_otp_note">
						' . wp_kses( mo_( "<b>For Indian customers</b>: To modify SMS templates, first register them on the DLT portal. Learn about the registration process at <u><i><a href='https://plugins.miniorange.com/dlt-registration-process-for-sending-sms' target='_blank' >DLT Registration</a></i></u>.<br>( If you are using the <b>miniOrange gateway</b> once the template is registered, contact us at <u><i><a style='cursor:pointer;' onClick='otpSupportOnClick();'>otpsupport@xecurify.com</a></i></u>. )" ), MoUtility::mo_allow_html_array() ) . '
							</div>
					</div>
				</div>
			</div>';

echo '
		</div>

		<div class="border-b px-mo-4" id="email_template">
			<div class="w-full flex m-mo-4">
				<div class="flex-1">
					<h5 class="mo-title">' . esc_html( mo_( 'Email Template Configurations' ) ) . '</h5>
					<p class="mo-caption mt-mo-2 mr-mo-8">' . esc_html( mo_( 'Customize your Email template and from Email address' ) ) . '</p>
				</div>
				<div class="flex-1">
					<div class="pr-mo-8 my-mo-6">
						<div class="mo_otp_note">
							To prevent spamming, we have enabled secure customization of Email templates. Contact us at <b>otpsupport@xecurify.com</b>.';
			mo_draw_tooltip(
				MoMessages::showMessage( MoMessages::EMAIL_SENDER_HEADER ),
				MoMessages::showMessage( MoMessages::EMAIL_SENDER_BODY )
			);
			echo '   	</div>
					</div>
				</div>
			</div>
		</div>';
