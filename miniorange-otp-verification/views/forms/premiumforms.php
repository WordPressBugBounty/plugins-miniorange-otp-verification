<?php
/**
 * Load admin view for Elementor pro free form.
 *
 * @package miniorange-otp-verification/handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

echo '<div class="mo_otp_form" id="premium_forms">
		<strong>' . wp_kses(
	$form_name,
	array(
		'b'    => array(),
		'span' => array(
			'style' => array(),
		),
	)
) . '</strong>
<b><span style="color:red"> [ ' . esc_html( mo_( $plan_name ) ) . ']</span></b><br>';


echo '
<div class="mo_otp_note mt-mo-6">
						' . esc_html( mo_( 'The OTP verification on ' ) ) . '<b>' . esc_html( mo_( $form_name ) ) . '</b>' . esc_html( mo_( ' plugin has been separately integrated in our premium plugins to provide users with Phone verification or Email Verification. ' ) ) . '<br>' . esc_html( mo_( 'To get access to this' ) ) . '<b>' . esc_html( mo_( ' Premium Feature ' ) ) . '</b>' . esc_html( mo_( ' please upgrade to the ' ) ) . '<b><a class="mo_links" href="' . esc_url( $license_url ) . '" target="_blank">' . esc_attr( $plan_name ) . '</a></b><br/><br/>' . esc_html( mo_( 'If you have any questions or concerns kindly contact us at ' ) ) . wp_kses(
	'<a style="cursor:pointer;" onClick="otpSupportOnClick();"><u> otpsupport@xecurify.com</u></a></div>',
	array(
		'a' => array(
			'style'   => array(),
			'onclick' => array(),
		),
		'u' => array(),
	)
);
echo '</div>';
