<?php
/**
 * Load admin view for OTP Settings Tab.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use OTP\Helper\MoConstants;
use OTP\Helper\MoMessages;
use OTP\Helper\MoUtility;

echo '<div id="otpSettingsSubTabContainer" class="mo-subpage-container ' . esc_attr( $hidden ) . '">
		<form name="f" method="post" action="" id="mo_otp_verification_settings">
			<input type="hidden" name="option" value="mo_otp_extra_settings" />';

				wp_nonce_field( $nonce );

echo '	    	<div class="mo-header">
					<p class="mo-heading flex-1">' . esc_html( mo_( 'OTP Settings' ) ) . '</p>
					<input type="submit" name="save" id="save" ' . esc_attr( $disabled ) . '
								class="mo-button inverted" value="' . esc_attr( mo_( 'Save Settings' ) ) . '">
				</div>';
				$gateway->template_configuration_page( $disabled );
echo '	        <div id="otpLengthValidity" class= "border-b flex flex-col gap-mo-6 px-mo-4">    
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">OTP Properties</h5>
							<p class="mo-caption mt-mo-2">OTPs will be sent as per the length and validity you set.<br>You can edit OTP Properties anytime.</p>
						</div>
						<div class="flex-1">';
if ( $show_transaction_options ) {
			echo '          <div class="flex-1 flex my-mo-6 gap-mo-4 pr-mo-8">
								<div class="mo_otp_note p-mo-4" >
									<a class="font-semibold" href="' . esc_url( MoConstants::FAQ_BASE_URL ) . 'change-length-otp/" target="_blank">
										' . esc_html( mo_( 'Click here to see how you can change OTP Length' ) ) . '
									</a>
								</div>
								<div class="mo_otp_note p-mo-4" >
									<a class="font-semibold" href="' . esc_url( MoConstants::FAQ_BASE_URL ) . 'change-time-otp-stays-valid/" target="_blank">
										' . esc_html( mo_( 'Click here to see how you can change OTP Validity' ) ) . '</span>
									</a>
								</div>
							</div>';
} else {

			echo '
							<div class="flex-1 flex my-mo-6 gap-mo-4 pr-mo-8">
								<div class="mo-input-wrapper">
									<label class="mo-input-label">OTP Length</label>
									<input class=" mo-input" placeholder="Enter the length of OTP" value="' . esc_attr( $mo_otp_length ) . '" type="text" name="mo_otp_length" >
								</div>
								<div class="mo-input-wrapper">
									<label class="mo-input-label">OTP Validity (in mins)</label>
									<input class=" mo-input" placeholder="Enter the validity of OTP" value="' . esc_attr( $mo_otp_validity ) . '" type="text" name="mo_otp_validity" >
								</div>
							</div>';
}


echo '
						</div>
					</div>
				</div>
				<div id="chosenOtpType" class= "border-b px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">Alphanumeric OTP Format</h5>
							<p class="mo-caption mt-mo-2 mr-mo-16">This feature enables admins to customize the OTP Format, including lowercase, uppercase, and numeric characters.<b> For eg: aB23Fm</b></p>
						</div>
						<div class="flex-1">';

$html = '				<div class="pb-mo-2 pr-mo-8">
							<div class="mo_otp_note flex gap-mo-1 my-mo-4">
								<svg width="18" class="my-mo-4 ml-mo-4" height="18" viewBox="0 0 24 24" fill="none">
										<g id="d4a43e0162b45f718f49244b403ea8f4">
											<g id="4ea4c3dca364b4cff4fba75ac98abb38">
												<g id="2413972edc07f152c2356073861cb269">
													<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
												</g>
											</g>
										</g>
									</svg>
								<div class="my-mo-5 mr-mo-4">This is a Enterprise Plan feature. Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.
											</a>
								</div>
							</div>
						</div>';

										$html = apply_filters( 'mo_alphanumeric_card_ui', $html );

										echo wp_kses(
											$html,
											array(
												'tr'     => array(),
												'td'     => array( 'class' => array() ),
												'div'    => array( 'class' => array() ),
												'span'   => array( 'style' => array() ),
												'input'  => array(
													'type' => array(),
													'name' => array(),
													'class' => array(),
													'checked' => array(),
													'value' => array(),
												),
												'a'      => array(
													'href' => array(),
													'class' => array(),
												),
												'svg'    => array(
													'class' => true,
													'width' => true,
													'height' => true,
													'viewbox' => true,
													'fill' => true,
												),
												'circle' => array(
													'id' => true,
													'cx' => true,
													'cy' => true,
													'cz' => true,
													'r'  => true,
													'stroke' => true,
													'stroke-width' => true,
												),
												'g'      => array(
													'fill' => true,
													'id'   => true,
												),
												'path'   => array(
													'd'    => true,
													'fill' => true,
													'id'   => true,
													'stroke' => true,
													'stroke-width' => true,
													'stroke-linecap' => true,
												),
											)
										);

										echo '       
					</div>
				</div>
			</div>';

										$master_otp_view_for_non_enterprise_plan = '
									<div class="pb-mo-2">
										<div class="mo_otp_note flex gap-mo-1 my-mo-4">
											<svg width="18" class="my-mo-4 ml-mo-4" height="18" viewBox="0 0 24 24" fill="none">
													<g id="d4a43e0162b45f718f49244b403ea8f4">
														<g id="4ea4c3dca364b4cff4fba75ac98abb38">
															<g id="2413972edc07f152c2356073861cb269">
																<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
															</g>
														</g>
													</g>
												</svg>
											<div class="my-mo-5 mr-mo-4">This is a Enterprise Plan feature. Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.
														</a>
											</div>
										</div>
									</div>';

										$master_otp_view_for_non_enterprise_plan = apply_filters( 'mo_masterotp_card_ui', $master_otp_view_for_non_enterprise_plan, $disabled );

										echo '
			<div id="masterotp" class= "border-b px-mo-4">
				<div class="w-full flex m-mo-4">
					<div class="flex-1">
						<h5 class="mo-title">Backup Code/Master OTP</h5>
						<p class="mo-caption mt-mo-2">Allows users to login with Backup Code/Master OTP in case of any gateway/OTP delivery failure.</p>
					</div>
					<div class="flex-1 px-mo-8 my-mo-6">
										' . wp_kses(
											$master_otp_view_for_non_enterprise_plan,
											array(
												'tr'     => array(),
												'i'      => array(),
												'button' => array(),
												'b'      => array(),
												'strong' => array(),
												'td'     => array( 'class' => array() ),
												'input'  => array(
													'type' => array(),
													'name' => array(),
													'class' => array(),
													'checked' => array(),
													'value' => array(),
													'readonly' => array(),
													'id'   => array(),
													'style' => array(),
													'placeholder' => array(),
													'disabled' => array(),
												),
												'div'    => array( 'class' => array() ),
												'span'   => array( 'style' => array() ),
												'table'  => array( 'style' => array() ),
												'a'      => array(
													'href' => array(),
													'class' => array(),
												),
												'label'  => array( 'class' => array() ),
												'svg'    => array(
													'class' => true,
													'width' => true,
													'height' => true,
													'viewbox' => true,
													'fill' => true,
												),
												'circle' => array(
													'id' => true,
													'cx' => true,
													'cy' => true,
													'cz' => true,
													'r'  => true,
													'stroke' => true,
													'stroke-width' => true,
												),
												'g'      => array(
													'fill' => true,
													'id'   => true,
												),
												'path'   => array(
													'd'    => true,
													'fill' => true,
													'id'   => true,
													'stroke' => true,
													'stroke-width' => true,
													'stroke-linecap' => true,
												),
											)
										) . '
					</div>
				</div>
			</div>
			<div id="autofillOTP" class= "border-b px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">Autofill OTP on Phone</h5>
							<p class="mo-caption mt-mo-2 mr-mo-16">This feature automatically fills the OTP in the input field when a user receives OTP on phone.</p>
						</div>
						<div class="flex-1">';

										$autofill_html     = '<div class="pb-mo-2 pr-mo-8">
								<div class="p-mo-6 flex items-center bg-blue-50 gap-mo-2">
									<svg width="25" style="margin-bottom: 11%;" height="25" viewBox="0 0 24 24" fill="none">
										<g id="d4a43e0162b45f718f49244b403ea8f4">
											<g id="4ea4c3dca364b4cff4fba75ac98abb38">
												<g id="2413972edc07f152c2356073861cb269">
													<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
												</g>
											</g>
										</g>
									</svg>
									<div class="grow">
										<p class="font-bold m-mo-0">Premium Feature</p>
										<p class="mo_otp_note m-mo-0">Please reach out to us for enabling Auto Fill OTP on your WordPress site.</p>
				
									</div>
									<a class="mo-button medium primary" style="cursor:pointer;float:right;width:27%;" onClick="otpSupportOnClick(\'Hi! I am interested in using Auto Fill OTP Feature for my website, can you please help me with more information?\');" >Contact Us</a>
								</div>
						</div>';
											$autofill_html = apply_filters( 'mo_autofill_otp_phone', $autofill_html );

										echo wp_kses(
											$autofill_html,
											array(
												'div'     => array( 'class' => array() ),
												'p'       => array( 'class' => array() ),
												'span'    => array( 'style' => array() ),
												'input'   => array(
													'type' => array(),
													'name' => array(),
													'class' => array(),
													'checked' => array(),
													'value' => array(),
												),
												'a'       => array(
													'href' => array(),
													'class' => array(),
													'style' => array(),
													'onclick' => array(),
												),
												'svg'     => array(
													'class' => true,
													'width' => true,
													'height' => true,
													'viewbox' => true,
													'fill' => true,
													'style' => true,
												),
												'g'       => array(
													'fill' => true,
													'id'   => true,
												),
												'path'    => array(
													'd'    => true,
													'fill' => true,
													'id'   => true,
													'stroke' => true,
													'stroke-width' => true,
													'stroke-linecap' => true,
												),
												'checked' => array(),
											)
										);

										echo '<div class="mo_otp_note w-[94%]">This feature will not work in <b>Safari</b> browser.</div>       
	                    </div>
                  </div>
            </div>
		</form>
	</div>';
