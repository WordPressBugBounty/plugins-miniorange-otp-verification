<?php
/**
 * Loads View for List of all the addons.
 *
 * @package miniorange-otp-verification
 */

use OTP\Helper\MoConstants;
$request_uri = remove_query_arg( array( 'addon', 'form', 'subpage' ), isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ); // phpcs:ignore -- false positive.
$license_url = add_query_arg( array( 'page' => 'mootppricing' ), $request_uri );

echo '
		<div class="mo-header">
			<p class="mo-heading flex-1">' . esc_html( mo_( 'Gateway Settings' ) ) . '</p>
			<input type="submit" name="save" ' . esc_attr( $disabled ) . '
						class="mo-button inverted" disabled value="' . esc_attr( mo_( 'Save Settings' ) ) . '">
		</div>
				<div class="border-b flex flex-col gap-mo-6 px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">' . esc_html( mo_( 'SMS Gateway Configurations' ) ) . '</h5>
							<div class="mo-caption mt-mo-2 mr-mo-20">' . wp_kses(
								mo_( 'SMS Gateway is a service provider for sending SMS on your behalf to your users. Your default SMS gateway is <b>miniOrange gateway</b>. You can <a style="cursor:pointer;" target="_blank"  href="' . esc_url( MOV_PORTAL ) . '/initializePayment?requestOrigin=wp_otp_verification_basic_plan" ><u><i>buy SMS transactions from miniOrange gateway</i></u></a> or <u><i><a href="' . esc_attr( $license_url ) . '" target="_blank" >check our gateway-based plans.</a></i></u>' ),
								array(
									'u' => array(),
									'i' => array(),
									'b' => array(),
									'a' => array(
										'href'   => array(),
										'style'  => array(),
										'target' => array(),
									),
								)
							);
echo '						</div>
						</div>
						<div class="flex-1 pr-mo-4 pl-mo-2 py-mo-4">
							<div class="flex">
								<div class="w-[46%] my-mo-2">' . esc_html( mo_( 'Select Gateway type' ) ) . ': </div>
								<div class="mo-select-wrapper w-[46%]">
									<select id="custom_gateway_type" disabled name="mo_customer_validation_custom_gateway_type">
										<option value="MoGateway">miniOrange Gateway</option>
									</select>									
								</div>
							</div>
							<div class="flex-1">
								<div class="pb-mo-2 pr-mo-10">
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
										<div class="my-mo-5 mr-mo-4">To use your custom SMS Gateway, upgrade to the premium plan. 
													<br>Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.
													</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="border-b flex flex-col gap-mo-6 px-mo-4">
					<div class="w-full flex m-mo-4">
						<div class="flex-1">
							<h5 class="mo-title">' . esc_html( mo_( 'Email Gateway(SMTP) Configurations' ) ) . '</h5>
							<p class="mo-caption mt-mo-2">' . esc_html( mo_( 'SMTP Gateway is a service provider for sending Emails on your behalf to your users.' ) ) . '</p>
							
						</div>
						<div class="flex-1 pr-mo-4 pl-mo-2 py-mo-4">
							<div class="flex-1">
								<div class="pb-mo-2 pr-mo-10">
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
										<div class="my-mo-5 mr-mo-4">To use your custom SMTP Gateway, upgrade to the premium plan. 
													<br>Check <a class="font-semibold text-yellow-500" href="' . esc_url( $license_url ) . '">Licensing Tab</a> to learn more.
													</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';

	echo '  <div class="border-b flex flex-col gap-mo-6 px-mo-4">
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
