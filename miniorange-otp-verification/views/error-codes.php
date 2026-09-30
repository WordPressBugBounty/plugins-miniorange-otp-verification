<?php
/**
 * Admin view for the Troubleshooting > Error Codes subtab.
 *
 * @var string $hidden 'hidden' CSS class when this subtab isn't active.
 *
 * @package miniorange-otp-verification/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$otp_error_codes = array(
	array(
		'code'     => 'WPOTPERR001',
		'category' => __( 'Email', 'miniorange-otp-verification' ),
		'cause'    => __( 'Email license has expired or is blocked.', 'miniorange-otp-verification' ),
		'solution' => __( 'Renew or activate your email license. Contact miniOrange support if the issue persists.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR002',
		'category' => __( 'Email', 'miniorange-otp-verification' ),
		'cause'    => __( 'The email gateway failed to send the OTP.', 'miniorange-otp-verification' ),
		'solution' => __( 'Check your email gateway configuration under Gateway Settings. Verify SMTP credentials and ensure the server is reachable.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR003',
		'category' => __( 'Phone / SMS', 'miniorange-otp-verification' ),
		'cause'    => __( 'Phone/SMS license has expired or is blocked.', 'miniorange-otp-verification' ),
		'solution' => __( 'Renew or activate your SMS license. Contact miniOrange support if the issue persists.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR004',
		'category' => __( 'Phone / SMS', 'miniorange-otp-verification' ),
		'cause'    => __( 'No SMS gateway is configured for the plugin.', 'miniorange-otp-verification' ),
		'solution' => __( 'Go to Gateway Settings and configure an SMS gateway or select a custom gateway type.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR005',
		'category' => __( 'Phone / SMS', 'miniorange-otp-verification' ),
		'cause'    => __( 'The SMS gateway failed to send the OTP.', 'miniorange-otp-verification' ),
		'solution' => __( 'Verify your SMS gateway credentials and account balance under Gateway Settings. Check gateway provider logs for more details.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR006',
		'category' => __( 'Security', 'miniorange-otp-verification' ),
		'cause'    => __( 'WordPress nonce verification failed. The security token was invalid or expired.', 'miniorange-otp-verification' ),
		'solution' => __( 'Reload the page and try again. If the issue persists, check for conflicting plugins that may be stripping nonce parameters.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR007',
		'category' => __( 'Login Flow', 'miniorange-otp-verification' ),
		'cause'    => __( 'The username or email entered does not match any existing WordPress user account.', 'miniorange-otp-verification' ),
		'solution' => __( 'Ensure the user account exists. Check for typos in the username or email address entered.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR008',
		'category' => __( 'Registration', 'miniorange-otp-verification' ),
		'cause'    => __( 'The phone number entered is already associated with another user account.', 'miniorange-otp-verification' ),
		'solution' => __( 'Use a different phone number, or log in with the account that already uses this number.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR009',
		'category' => __( 'Session', 'miniorange-otp-verification' ),
		'cause'    => __( 'The session data is invalid, missing, or has expired before OTP verification completed.', 'miniorange-otp-verification' ),
		'solution' => __( 'Reload the page to start a fresh session and request a new OTP.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR010',
		'category' => __( 'Verification', 'miniorange-otp-verification' ),
		'cause'    => __( 'The phone number entered during verification does not match the number the OTP was sent to.', 'miniorange-otp-verification' ),
		'solution' => __( 'Re-enter the same phone number that was used when requesting the OTP.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR011',
		'category' => __( 'Verification', 'miniorange-otp-verification' ),
		'cause'    => __( 'The OTP entered by the user is incorrect.', 'miniorange-otp-verification' ),
		'solution' => __( 'Double-check the OTP received via SMS or email. If expired, use Resend OTP to request a new one.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR012',
		'category' => __( 'Authentication', 'miniorange-otp-verification' ),
		'cause'    => __( 'The user is not logged in, but this action requires an active WordPress session.', 'miniorange-otp-verification' ),
		'solution' => __( 'Log in to your WordPress account and try again.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR014',
		'category' => __( 'Email', 'miniorange-otp-verification' ),
		'cause'    => __( 'The email address entered does not match the format expected by the plugin.', 'miniorange-otp-verification' ),
		'solution' => __( 'Double-check the email address for typos and re-enter it in the correct format.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR015',
		'category' => __( 'Phone / SMS', 'miniorange-otp-verification' ),
		'cause'    => __( 'The phone number entered does not match the format expected by the plugin.', 'miniorange-otp-verification' ),
		'solution' => __( 'Double-check the phone number, including the country code, and re-enter it in the correct format.', 'miniorange-otp-verification' ),
	),
	array(
		'code'     => 'WPOTPERR016',
		'category' => __( 'Verification', 'miniorange-otp-verification' ),
		'cause'    => __( 'The email address entered during verification does not match the address the OTP was sent to.', 'miniorange-otp-verification' ),
		'solution' => __( 'Re-enter the same email address that was used when requesting the OTP.', 'miniorange-otp-verification' ),
	),
);
?>
<div id="errorCodesSubTabContainer" class="mo-subpage-container <?php echo esc_attr( $hidden ); ?>">

	<div class="mo-header">
		<p class="mo-heading flex-1"><?php echo esc_html__( 'OTP Verification - Error Code Reference', 'miniorange-otp-verification' ); ?></p>
	</div>

	<div class="border-b px-mo-4 py-mo-4">
		<p class="mo-caption"><?php echo esc_html__( 'When an error occurs during OTP verification, an error code is displayed to the user. Use the table below to identify the cause and apply the appropriate fix.', 'miniorange-otp-verification' ); ?></p>
	</div>

	<div class="px-mo-4 py-mo-4">
		<div class="bg-white rounded-md overflow-x-auto">
			<table id="mo_error_codes_table" class="mo-table mo-reporting-table w-full">
				<thead>
					<tr class="mo_report_table_heading">
						<th class="px-4 py-2 text-center" style="width:12%;"><?php echo esc_html__( 'Error Code', 'miniorange-otp-verification' ); ?></th>
						<th class="px-4 py-2 text-center" style="width:12%;"><?php echo esc_html__( 'Category', 'miniorange-otp-verification' ); ?></th>
						<th class="px-4 py-2 text-center" style="width:35%;"><?php echo esc_html__( 'Cause', 'miniorange-otp-verification' ); ?></th>
						<th class="px-4 py-2 text-center"><?php echo esc_html__( 'Solution', 'miniorange-otp-verification' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $otp_error_codes as $entry ) : ?>
					<tr>
						<td class="px-4 py-2 text-center"><?php echo esc_html( $entry['code'] ); ?></td>
						<td class="px-4 py-2 text-center"><?php echo esc_html( $entry['category'] ); ?></td>
						<td class="px-4 py-2"><?php echo esc_html( $entry['cause'] ); ?></td>
						<td class="px-4 py-2"><?php echo esc_html( $entry['solution'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

</div>
