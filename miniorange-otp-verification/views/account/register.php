<?php
/**
 * Load admin view for miniorange Registration Form.
 *
 * @package miniorange-otp-verification/views
 */

echo '	<form name="f" method="post" action="" id="register-form" class="mo-content-wrapper p-mo-32 justify-center items-center">';
			wp_nonce_field( $nonce );
echo '		<input type="hidden" name="option" value="mo_registration_register_customer" />
			<div class="bg-white rounded-xl w-[75%] px-mo-32 flex flex-col gap-mo-4">
				<p class="mo-heading mb-mo-6">Create new account</p>
				<div class="w-full mo-input-wrapper group group">
					<label  class="mo-input-label">Email</label>
					<input class="w-full mo-input" type="email" name="email"
							required placeholder=""
							value="' . esc_attr( $mo_current_user->user_email ) . '" />
				</div>

				<div class="w-full mo-input-wrapper group group">
					<label  class="mo-input-label">Website or Company Name</label>
					<input class="w-full mo-input" type="text" name="company"
							required placeholder=""
							value="' . esc_attr( sanitize_text_field( isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '' ) ) . '" />
				</div>
				
				<div class="flex items-center gap-mo-4">

					<div class="w-full mo-input-wrapper group group">
						<label  class="mo-input-label">First Name</label>
						<input class="w-full mo-input" type="text" name="fname"
							placeholder=""
							value="' . esc_attr( $mo_current_user->user_firstname ) . '" />
					</div>
				
					<div class="w-full mo-input-wrapper group group">
						<label  class="mo-input-label">Last Name</label>
						<input class="w-full mo-input" type="text" name="lname"
							placeholder=""
							value="' . esc_attr( $mo_current_user->user_lastname ) . '" />
					</div>

				</div>

				<div class="flex items-center gap-mo-4">

					<div class="w-full mo-input-wrapper group group">
						<label class="mo-input-label">Password Min. Length 6</label>
						<input class="w-full mo-input" type="password" name="password"
							placeholder="" />
					</div>
				
					<div class="w-full mo-input-wrapper group group">
						<label class="mo-input-label">Confirm Password</label>
						<input class="w-full mo-input" type="password" name="confirmPassword"
							placeholder="" />
					</div>

				</div>
				<div class="flex gap-mo-2">
				<input  type="checkbox"
								class="form_options" 
								style="margin-top: 0.1rem;"
								id="mo_agree_plugin_policy" 
								name="mo_customer_validation_agree_plugin_policy" 
								value="1"/> 
						<span>' . wp_kses(
								mo_( 'I have read and agree to the <u><i><a target="_blank" style="cursor:pointer;" href="https://plugins.miniorange.com/end-user-license-agreement">end user agreement</a></i></u> and <u><i><a target="_blank" href="https://plugins.miniorange.com/wp-content/uploads/2023/08/Plugins-Privacy-Policy.pdf" style="cursor:pointer;">plugin privacy policy.</a></i></u>' ),
								array(
									'a' => array(
										'target' => array(),
										'style'  => array(),
										'href'   => array(),
									),
									'u' => array(),
									'i' => array(),
								)
							) . '</span>
				</div>

				<input type="submit" disabled id="mo_user_register" name="submit" value="' . esc_attr( mo_( 'Register' ) ) . '"
							class="mo-button primary" />
				<a href="#goToLoginPage" class="mo-button secondary">' . esc_attr( mo_( 'Already Have an Account? Sign In' ) ) . '</a>

			</div>
		</form>
		<form id="goToLoginPageForm" method="post" action="">';
		wp_nonce_field( $nonce );
echo '		<input type="hidden" name="option" value="mo_go_to_login_page" />
		</form>
		<script>
			jQuery(document ) .  ready(function(){
				$mo(\'a[href="#mo_forgot_password"]\' ) .  click(function(){
					$mo("#forgotpasswordform" ) .  submit();
				});
			
				$mo(\'a[href="#goToLoginPage"]\' ) .  click(function(){
					$mo("#goToLoginPageForm" ) .  submit();
				});
			});
		</script>';
