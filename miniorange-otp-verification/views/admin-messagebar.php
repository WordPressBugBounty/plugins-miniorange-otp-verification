<?php
/**
 * Loads View for message bar on admin dashboard.
 *
 * @package miniorange-otp-verification
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
echo '			<!-- Admin Message Bar -->
		<div>';
if ( ! $registered ) {
	echo '<div class="mo-alert-container mo-alert-error">
					<span>' . wp_kses(
		$register_msg,
		array(
			'a' => array( 'href' => array() ),
			'i' => array( 'href' => array() ),
			'u' => array( 'href' => array() ),
		)
	) . '			</span>
				</div>';
} elseif ( $is_free_plugin && ( '0' === $remaining_sms && 'DEMO' === $license_plan ) ) {
	echo '<div class="mo-alert-container mo-alert-error">
			<span>You don\'t have SMS transactions in your account. Contact us at <a style="cursor:pointer;" onClick="otpSupportOnClick(\'Hi! Could you please provide me with the 10 free SMS transactions for testing purposes? \');"><u> otpsupport@xecurify.com</u> </a> to avail one-time 10 free SMS transactions or <u><i><a target="_blank" href="' . esc_url( MOV_PORTAL ) . '/initializePayment?requestOrigin=wp_otp_verification_basic_plan">' . esc_html( mo_( ' Recharge' ) ) . '</a></u></i> your account.</span>
</div>';
} elseif ( ! $activated ) {
	echo '<div class="mo-alert-container mo-alert-error">
					<span>' . wp_kses(
		$activation_msg,
		array(
			'a' => array( 'href' => array() ),
			'i' => array( 'href' => array() ),
			'u' => array( 'href' => array() ),
		)
	) . '			</span>
				</div>';
} elseif ( ! $gatewayconfigured ) {
	echo '<div class="mo-alert-container mo-alert-error">
					<span>' . wp_kses(
		$gateway_msg,
		array(
			'a' => array( 'href' => array() ),
			'i' => array( 'href' => array() ),
			'u' => array( 'href' => array() ),
		)
	) . '			</span>
				</div>';
}
echo '  </div>';


