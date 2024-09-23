<?php
/**
 * Load admin view for miniorange Login Form.
 *
 * @package miniorange-otp-verification/views
 */

echo '	<form name="f" method="post" action="" class="mo-content-wrapper p-mo-32 justify-center items-center">';
			wp_nonce_field( $nonce );
echo '		<input type="hidden" name="option" value="mo_registration_connect_verify_customer" />
	<div class="bg-white rounded-xl w-[75%] flex px-mo-32 flex-col gap-mo-4">
		 <p class="mo-heading mb-mo-6">Login in using your miniorange account</p>
		 <div class="w-full mo-input-wrapper group group">
			 <label class="mo-input-label">Email</label>
			 <input class="w-full mo-input" type="email" name="email" value="' . esc_attr( $admin_email ) . '"
						 required placeholder=""/>
		 </div>

		 <div class="w-full mo-input-wrapper group group">
			 <label class="mo-input-label">Password</label>
			 <input class="w-full mo-input" required type="password"
						 name="password" placeholder="" />
		 </div>			

		 <div><a href="https://login.xecurify.com/moas/idp/resetpassword" target="_blank" class="text-right font-bold hover:underline float-right">Forgot Password</a></div>
		 <input type="submit" class="mo-button inverted" value="Login"/>
		 <a href="#goBackButton" class="mo-button secondary">Register</a>

	</div>
</form>
<form id="forgotpasswordform" method="post" action="">';
	wp_nonce_field( $nonce );
echo '		<input type="hidden" name="option" value="mo_registration_mo_forgot_password" />
</form>
<form id="goBacktoRegistrationPage" method="post" action="">';
	wp_nonce_field( $nonce );
echo '		<input type="hidden" name="option" value="mo_registration_go_back" />
</form>
<script>
	jQuery(document).ready(function(){
		 $mo(\'a[href="#mo_forgot_password"]\').click(function(){
			 $mo("#forgotpasswordform").submit();
		 });

		 $mo(\'a[href="#goBackButton"]\').click(function(){
			 $mo("#goBacktoRegistrationPage").submit();
		 });
	 });
</script>';
