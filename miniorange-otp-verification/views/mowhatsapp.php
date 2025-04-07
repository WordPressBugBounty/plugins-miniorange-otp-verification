<?php
/**
 * Load admin view for WhatsApp Tab.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
use OTP\Helper\MoUtility;
use OTP\Helper\MoConstants;


$circle_icon = '
	<svg class="min-w-[8px] min-h-[8px]" width="8" height="8" viewBox="0 0 18 18" fill="none">
		<circle id="a89fc99c6ce659f06983e2283c1865f1" cx="9" cy="9" r="7" stroke="rgb(99 102 241)" stroke-width="4"></circle>
	</svg>
	';

$whatsapp_view = '
	<div id="mo-new-pricing-page" class="mo-new-pricing-page mt-mo-4 bg-white rounded-md">

		<!--  TABS  -->
		<div class="mo-tab-container" style="padding-top: 10px; padding-bottom: 20px;">
			<h2 style=" font-size:1.300rem;" class="mo-heading pl-mo-4">' . esc_html( mo_( 'WhatsApp OTP Verification And Notifications' ) ) . '</h2>          
		</div>

		<!--  TABS CONTENT  -->
		<div id="whatsapp-tab-content">
			<!--  TEST GATEWAY AND PRICING SECTION  -->
			<section id="mo_otp_plans_pricing_table">
				<div>
					<div class="whatsapp-test-configuration px-mo-16">

						<div class="mo-title flex-1" style="width:50%;" >
							<p class="mo_wa_note" style="">' . esc_html( mo_( 'This feature allows you to configure WhatsApp for OTP Verification as well as sending WooCommerce notifications and alerts via WhatsApp using the default miniOrange Business account or your personal business account.' ) ) . '
							</p>
						</div>
						<form name="f" method="post" action="" id="mo_whatsapp_settings" >
							<input type="hidden" id="mo_admin_actions" name="mo_admin_actions" value="' . wp_create_nonce( 'mo_admin_actions' ) . '"/>
				
						</form>

					</div>
				</div>

				<div class="mo-whatsapp-snippet-grid">
					<div class="mo-whatsapp-card" >
						<div class="mo-whatsapp-header">
								<h5>' . esc_html( mo_( 'WhatsApp Premium Plan Features' ) ) . '</h5> &nbsp;&nbsp; 
								<div class="mb-mo-1"> [
									<i><a href="' . esc_url( $license_url ) . '"	target="_blank">' . esc_html( mo_( ' Supported in WooCommerce Plan ' ) ) . '</a></i> ]
								</div>	
								<svg width="18" class="ml-mo-2 mr-mo-2 mb-mo-1" height="18" viewBox="0 0 24 24" fill="none">
											<g id="d4a43e0162b45f718f49244b403ea8f4">
												<g id="4ea4c3dca364b4cff4fba75ac98abb38">
													<g id="2413972edc07f152c2356073861cb269">
														<path id="2deabe5f8681ff270d3f37797985a977" d="M20.8007 20.5644H3.19925C2.94954 20.5644 2.73449 20.3887 2.68487 20.144L0.194867 7.94109C0.153118 7.73681 0.236091 7.52728 0.406503 7.40702C0.576651 7.28649 0.801941 7.27862 0.980492 7.38627L7.69847 11.4354L11.5297 3.72677C11.6177 3.54979 11.7978 3.43688 11.9955 3.43531C12.1817 3.43452 12.3749 3.54323 12.466 3.71889L16.4244 11.3598L23.0197 7.38654C23.1985 7.27888 23.4233 7.28702 23.5937 7.40728C23.7641 7.52754 23.8471 7.73707 23.8056 7.94136L21.3156 20.1443C21.2652 20.3887 21.0501 20.5644 20.8007 20.5644Z" fill="orange"></path>
													</g>
												</g>
											</g>
								</svg>
						</div> 

						<ul class="mt-mo-4 grow" >

							<li class="wa-feature-snippet">
								<span class="mt-mo-1">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
								<p class="m-mo-0">Use the default <b>miniOrange business account</b>. Need to purchase WhatsApp transactions from <b>miniOrange</b>.</p>
							</li>

							<li class="wa-feature-snippet">
								<span class="mt-mo-1">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
								<p class="m-mo-0">Use your <b>personal business account</b>. Need to purchase WhatsApp transactions from <b>Meta(Facebook)</b></p>
							</li>

							<li class="wa-feature-snippet">
								<span class="mt-mo-1">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
								<p class="m-mo-0">WhatsApp <b>OTP Verification</b> and <b>notification</b>.</p>
							</li> 

							<li class="wa-feature-snippet">
								<span class="mt-mo-1">' . wp_kses( $circle_icon, MoUtility::mo_allow_svg_array() ) . '</span>
								<p class="m-mo-0"><b>Fallback to SMS</b> OTP for non-WhatsApp numbers.</p>
							</li>

						</ul>

						<a class="w-full mo-button primary" href="' . esc_url( $license_url ) . '" target="_blank">' . esc_html( mo_( 'Upgrade Now' ) ) . '</a><br>
					</div>
				</div>   
			</section> 
		</div>
	</div>';

$whatsapp_view = apply_filters( 'mo_wa_premium_view', $whatsapp_view );

echo wp_kses(
	$whatsapp_view,
	array(
		'div'      => array(
			'name'   => array(),
			'id'     => array(),
			'class'  => array(),
			'title'  => array(),
			'style'  => array(),
			'hidden' => array(),
		),
		'span'     => array(
			'class'  => array(),
			'title'  => array(),
			'style'  => array(),
			'hidden' => array(),
		),
		'textarea' => array(
			'id'          => array(),
			'class'       => array(),
			'name'        => array(),
			'row'         => array(),
			'style'       => array(),
			'placeholder' => array(),
			'readonly'    => array(),
		),
		'input'    => array(
			'type'          => array(),
			'id'            => array(),
			'name'          => array(),
			'value'         => array(),
			'class'         => array(),
			'tabindex'      => array(),
			'hidden'        => array(),
			'style'         => array(),
			'placeholder'   => array(),
			'disabled'      => array(),
			'data-toggle'   => array(),
			'data-previous' => array(),
			'checked'       => array(),
		),
		'strong'   => array(),
		'form'     => array(
			'name'   => array(),
			'method' => array(),
			'action' => array(),
			'id'     => array(),
			'hidden' => array(),
		),
		'a'        => array(
			'href'   => array(),
			'target' => array(),
			'class'  => array(),
		),
		'i'        => array(),
		'p'        => array(
			'class' => array(),
			'style' => array(),
		),
		'b'        => array(),
		'li'       => array(
			'class'  => array(),
			'hidden' => array(),
		),
		'section'  => array(
			'id' => array(),
		),
		'label'    => array(
			'class' => array(),
		),
		'ul'       => array(
			'class' => array(),
		),
		'h1'       => array(
			'class' => array(),
		),
		'h2'       => array(
			'class' => array(),
			'style' => array(),
		),
		'h4'       => array(
			'class' => array(),
			'style' => array(),
		),
		'h5'       => array(
			'class' => array(),
			'style' => array(),
		),
		'svg'      => array(
			'class'   => true,
			'width'   => true,
			'height'  => true,
			'viewbox' => true,
			'fill'    => true,
		),
		'circle'   => array(
			'id'           => true,
			'cx'           => true,
			'cy'           => true,
			'cz'           => true,
			'r'            => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'g'        => array(
			'fill' => true,
			'id'   => true,
		),
		'path'     => array(
			'd'              => true,
			'fill'           => true,
			'id'             => true,
			'stroke'         => true,
			'stroke-width'   => true,
			'stroke-linecap' => true,
		),
	)
);

echo '
	<div id="whatsapp_test_pop_up" style="display:none;">
		<div id="mo_notice_modal" name="mo_test_whatsapp">
			<div class="mo_customer_validation-modal-backdrop ">
			</div>

			<div id="popup-modal" class="mo-popup-modal">
                <div id="whatsapp_show_popup" class="mo-popup-modal-wrapper">
				 	<div class="mo-popup-header-wrapper" style="border-bottom: 1px groove; background-color:#d1f7d9;">

                        <div class="mo-popup-text-wrapper mo-center" style="color:black;">
                            ' . esc_html( mo_( 'Test WhatsApp OTP' ) ) . '
                        </div>

                        <button type="button" id="mo_close_wp_pop_up_button" class="mo-popup-close-button" data-modal-hide="staticModal">
                            <svg class="w-mo-6 h-mo-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>

					<div class="px-mo-5" style="background-color: white;">
						<div class="py-mo-2 rounded-lg ">
							<div class="p-mo-4 text-xs font-semibold rounded-lg bg-blue-50" role="alert">
								Enter the below details to test the WhatsApp OTP on the Entered Phone number.
							</div>
						</div>
                	</div>

					<div class="px-mo-5" id="mo_whatsapp_test_details">
						<div class="pt-mo-4">
							<div class="mo-input-wrapper">
								<label class="mo-input-label">' . esc_html( mo_( 'Phone Number' ) ) . '</label>
								<input class="mo-form-input" required style="width:300px;" id="wa_test_configuration_phone" placeholder="Enter Phone Number with country code." type="text" name="wa_test_configuration_phone" >
							</div>
						</div>

						<div class="pt-mo-4">
							<div class="mo-input-wrapper">
								<label class="mo-input-label">' . esc_html( mo_( 'miniOrange Account Password' ) ) . '</label>
								<input class=" mo-form-input" required type="password" style="width:300px;" id="wa_test_configuration_password" placeholder="Enter miniOrange accounts password." type="text" name="wa_test_configuration_password" />
							</div>
						</div>	

						<div  name="mo_test_config_hide_response"  class="vfb-item" id="test_config_response" style="width:100%; display: none; padding: 10px 20px;border-radius: 10px; margin-top: 16px;"></div>

						<div class="my-mo-4">
							<input 	class="w-full mo-button inverted "  type="button"
								name="mo_gateway_submit" ' . esc_attr( $disabled ) . '
								id="whatsapp_gateway_submit"
								value="Send WhatsApp OTP"/>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>';




