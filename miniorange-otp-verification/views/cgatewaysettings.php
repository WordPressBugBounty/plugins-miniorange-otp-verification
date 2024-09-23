<?php
/**
 * Loads View for List of all the addons.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$request_uri = remove_query_arg( array( 'addon', 'form', 'subpage' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); // phpcs:ignore -- false positive.
$license_url = add_query_arg( array( 'page' => 'mootppricing' ), $request_uri );

echo '	<form name="f" method="post" action="" id="sms-configuration-form">';
			wp_nonce_field( $nonce );
	echo '	<input type="hidden" name="option" value="mo_customer_validation_gateway_configuration" />
			<div class="mo-header">
				<p class="mo-heading flex-1">' . esc_html( mo_( 'Gateway Settings' ) ) . '</p>
				<input type="submit" name="save" ' . esc_attr( $disabled ) . '
							class="mo-button inverted" value="' . esc_attr( mo_( 'Save Settings' ) ) . '">
			</div>
				<div class="border-b flex flex-col gap-mo-6 px-mo-4">
						<div class="w-full flex m-mo-4">
							<div class="flex-1">
								<h5 class="mo-title">' . esc_html( $sms_gateway_title ) . '</h5>
								<p class="mo-caption mt-mo-2 mr-mo-8">' . esc_html( mo_( ' Configure Your SMS Gateway to send OTPs and notifications' ) ) . '</p>
							</div>
							<div class="flex-1 pr-mo-4 pl-mo-2 py-mo-4" id="gateway">
								<div class="flex">
									<div class="w-[46%] my-mo-2">' . esc_html( mo_( 'Select Gateway Request type' ) ) . ': </div>
									<div class="mo-select-wrapper w-[46%]">
										<select id="custom_gateway_type" ' . esc_attr( $disabled ) . ' name="mo_customer_validation_custom_gateway_type">' .
										wp_kses(
											$gateway_list,
											array(
												'option' => array(
													'value' => array(),
												),
											)
										) .
										'</select>									
									</div>
								</div>
								<div id="gateway_configuration_fields" class="mt-mo-4">'
								. wp_kses(
									$gateway_config_view,
									array(
										'tr'       => array(),
										'li'       => array(),
										'ol'       => array(),
										'&nbsp'    => array(),
										'b'        => array(),
										'i'        => array(),
										'br'       => array(),
										'u'        => array(),
										'label'    => array(
											'for'   => array(),
											'style' => array(),
											'class' => array(),
										),
										'td'       => array(
											'class' => array(),
											'style' => array(),
										),
										'div'      => array(
											'style' => array(),
											'class' => array(),
											'id'    => array(),
										),
										'span'     => array( 'style' => array() ),
										'a'        => array(
											'href'   => array(),
											'target' => array(),
										),
										'input'    => array(
											'type'        => array(),
											'checked'     => array(),
											'id'          => array(),
											'name'        => array(),
											'value'       => array(),
											'class'       => array(),
											'hidden'      => array(),
											'style'       => array(),
											'placeholder' => array(),
											'disabled'    => array(),
											'readonly'    => array(),
											'required'    => array(),
										),
										'textarea' => array(
											'id'          => array(),
											'name'        => array(),
											'value'       => array(),
											'class'       => array(),
											'hidden'      => array(),
											'style'       => array(),
											'placeholder' => array(),
											'disabled'    => array(),
											'readonly'    => array(),
											'required'    => array(),
											'rows'        => array(),
										),
									)
								) .
							'	</div>
								<div class="my-mo-4">' . esc_html( $test_configuration_title ) . '</div>
								<div class="flex gap-mo-4">
									<div class="mo-input-wrapper flex-1 mt-mo-4">
										<label class="mo-input-label">Phone Number</label>
										<input class=" mo-form-input w-full" ' . esc_attr( $disabled ) . ' placeholder="Phone Number With Country Code" type="text" name="mo_test_configuration_phone" >
									</div>
									<div class="flex-1">
										<input  type="button" ' . esc_attr( $disabled ) . '
											name="mo_gateway_submit"
											class="mo-button primary my-mo-4"
											id="gateway_submit" value="' . esc_attr( $test_configuration_submit_button_txt ) . '" />
									</div>
										
								</div>
								<div name="mo_test_config_hide_response" id="test_config_hide_response" style="display:none;" >
									<div class="mo-input-wrapper w-[95%] pr-mo-8">
										<label class="mo-input-label font-bold rounded-md">' . esc_html( $test_configuration_response ) . '</label>
										<textarea readonly ' . esc_attr( $disabled ) . ' id="test_config_response" name="mo_test_configuration_response" placeholder="' . esc_html( mo_( ' Your Gateway Response ' ) ) . '" rows="4" maxlength="400" class="mo-textarea" ></textarea>
									</div>
								</div>
							</div>
						</div>
				</div>
				<div class="border-b flex flex-col gap-mo-6 px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">' . esc_html( mo_( 'Email Gateway(SMTP) Configurations' ) ) . '</h5>
							<p class="mo-caption mt-mo-2 mr-mo-20">' . esc_html( mo_( 'SMTP Gateway is a service provider for sending Emails on your behalf to your users.' ) ) . '</p>
							
						</div>
						<div class="flex-1 pr-mo-4 pl-mo-2 py-mo-4">
							<div class="flex-1">
								<div class="pb-mo-2 pr-mo-10">
									<div class="mo_otp_note my-mo-4">
										<div class="my-mo-5 mr-mo-4">
											You can configure your SMTP gateway from any third party SMTP plugin( For e.g <u><i><a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank" >WP SMTP</a></i></u> ) or php.ini file.<br>
											<b>Note:</b> You don\'t need to configure any extra settings in our plugin.
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';

		$html = '<div class="border-b flex flex-col gap-mo-6 px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">' . esc_html( 'SMS Backup Gateway Configuration' ) . '</h5>
							<p class="mo-caption mt-mo-2 mr-mo-20">' . esc_html( mo_( 'When the primary gateway is unavailable, the backup SMS gateway takes over and sends the SMS messages to recipients.' ) ) . '</p>
						</div>
						<div class="flex-1">
							<div class="pb-mo-2 pr-mo-10">
								<div class="mo_otp_note flex gap-mo-1 my-mo-4 w-[96%]">
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
							</div>
						</div>
					</div>
				</div>';
		$html = apply_filters( 'mo_smsbackupgateway_card__ui', $html, $disabled );
		echo wp_kses(
			$html,
			array(
				'table'    => array(
					'class' => array(),
					'style' => array(),
				),
				'tr'       => array( 'id' => array() ),
				'td'       => array( 'class' => array() ),
				'hr'       => array(),
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
				),
				'a'        => array(
					'href'  => array(),
					'class' => array(),
				),
				'p'        => array(
					'class' => array(),
					'style' => array(),
				),
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
			),
		);
		echo '</form>
	
			<script>
				jQuery("#customemaileditor").prop("required",true);
				jQuery("#custom_gateway_type").val("' . esc_attr( $active_gateway ) . '");
				jQuery("#gateway_submit");
			</script>';
